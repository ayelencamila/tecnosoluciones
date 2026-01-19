<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compras\StoreOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\OfertaCompra;
use App\Models\EstadoOrdenCompra;
use App\Repositories\OrdenCompraRepository;
use App\Services\Compras\RegistrarCompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Exception;

/**
 * Controlador de Órdenes de Compra (CU-22 y CU-24)
 * 
 * Responsabilidades:
 * - Coordinar casos de uso (GRASP: Controller)
 * - Delegar consultas complejas al Repository (Sommerville)
 * - Delegar lógica de negocio al Service (Larman)
 */
class OrdenCompraController extends Controller
{
    public function __construct(
        protected RegistrarCompraService $registrarCompraService,
        protected OrdenCompraRepository $ordenCompraRepository
    ) {}

    /**
     * CU-24: Lista todas las órdenes de compra con filtros
     * MEJORA: Usa Repository para encapsular queries complejas
     */
    public function index(Request $request): Response
    {
        // Delegar filtrado al repository (Sommerville: Separación de responsabilidades)
        $ordenes = $this->ordenCompraRepository->filtrar(
            criterios: $request->only(['numero_oc', 'proveedor_id', 'estado_id', 'fecha_desde', 'fecha_hasta', 'producto_id']),
            perPage: 15
        );

        // Obtener datos auxiliares desde repository
        $estados = $this->ordenCompraRepository->obtenerEstadosActivos();

        return Inertia::render('Compras/Ordenes/Index', [
            'ordenes' => $ordenes,
            'estados' => $estados,
            'filters' => $request->only(['numero_oc', 'proveedor_id', 'estado_id', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Genera una nueva Orden de Compra desde una oferta elegida
     * 
     * CU-22 Flujo Principal:
     * 1. Admin accede a oferta elegida
     * 2. Admin presiona "Generar OC"
     * 3. Sistema valida oferta
     * 4. Sistema genera OC con detalles
     * 5. Sistema genera PDF
     * 6. Sistema envía WhatsApp al proveedor
     * 7. Sistema notifica por email
     */
    public function store(StoreOrdenCompraRequest $request): RedirectResponse
    {
        try {
            $orden = $this->registrarCompraService->ejecutar(
                ofertaId: $request->validated('oferta_id'),
                usuarioId: $request->user()->id,
                observaciones: $request->validated('observaciones')
            );

            return redirect()
                ->route('ordenes.show', $orden->id)
                ->with('success', "Orden de Compra {$orden->numero_oc} generada exitosamente. Se envió WhatsApp al proveedor.");

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al generar la Orden de Compra: ' . $e->getMessage());
        }
    }

    /**
     * CU-24: Muestra el detalle de una Orden de Compra
     * Incluye historial de recepciones (CU-24 paso 6)
     */
    public function show(int $id): Response
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'oferta.solicitud',
            'detalles.producto',
            'estado',
            'usuario',
            // CU-24: Historial de recepciones
            'recepciones' => fn($q) => $q->with('usuario:id,name')->latest('fecha_recepcion'),
        ])->findOrFail($id);

        return Inertia::render('Compras/Ordenes/Show', [
            'orden' => $orden,
        ]);
    }

    /**
     * Descarga el PDF de la Orden de Compra
     */
    public function descargarPdf(int $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if (!$orden->archivo_pdf || !Storage::disk('public')->exists($orden->archivo_pdf)) {
            // Si no existe el PDF, regenerarlo
            $this->registrarCompraService->regenerarPdf($orden);
            $orden->refresh();
        }

        if (!$orden->archivo_pdf || !Storage::disk('public')->exists($orden->archivo_pdf)) {
            return back()->with('error', 'No se pudo generar el PDF de la orden.');
        }

        return Storage::disk('public')->download(
            $orden->archivo_pdf,
            "{$orden->numero_oc}.pdf"
        );
    }

    /**
     * Reenvía el WhatsApp al proveedor (para reintentos manuales)
     * 
     * Excepción 11a: Si el envío inicial falló, permitir reintento manual
     */
    public function reenviarWhatsApp(int $id): RedirectResponse
    {
        try {
            $orden = OrdenCompra::with('proveedor')->findOrFail($id);
            
            $this->registrarCompraService->reenviarWhatsApp($orden);

            return back()->with('success', "WhatsApp reenviado al proveedor {$orden->proveedor->razon_social}.");

        } catch (Exception $e) {
            return back()->with('error', 'Error al reenviar WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Reenvía el Email al proveedor (para reintentos manuales)
     */
    public function reenviarEmail(int $id): RedirectResponse
    {
        try {
            $orden = OrdenCompra::with('proveedor')->findOrFail($id);
            
            $this->registrarCompraService->reenviarEmail($orden);

            return back()->with('success', "Email reenviado al proveedor {$orden->proveedor->email}.");

        } catch (Exception $e) {
            return back()->with('error', 'Error al reenviar email: ' . $e->getMessage());
        }
    }

    /**
     * Regenera el PDF de la orden
     */
    public function regenerarPdf(int $id): RedirectResponse
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            
            $this->registrarCompraService->regenerarPdf($orden);

            return back()->with('success', "PDF regenerado exitosamente para OC {$orden->numero_oc}.");

        } catch (Exception $e) {
            return back()->with('error', 'Error al regenerar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Marca la orden como confirmada por el proveedor
     */
    public function confirmar(int $id): RedirectResponse
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            $orden->marcarConfirmada();

            return back()->with('success', "Orden {$orden->numero_oc} marcada como confirmada.");

        } catch (Exception $e) {
            return back()->with('error', 'Error al confirmar la orden: ' . $e->getMessage());
        }
    }

    /**
     * Cancela la orden de compra
     */
    public function cancelar(int $id): RedirectResponse
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            $motivo = request('motivo', 'Cancelado por el administrador');
            
            $orden->cancelar($motivo);

            return redirect()
                ->route('ordenes.index')
                ->with('success', "Orden {$orden->numero_oc} cancelada.");

        } catch (Exception $e) {
            return back()->with('error', 'Error al cancelar la orden: ' . $e->getMessage());
        }
    }
}
