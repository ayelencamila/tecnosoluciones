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
     * CU-22 Pantalla 1 (Kendall): Lista de Ofertas Elegidas para convertir en OC
     * Vista de Selección - Cap. 11 Salida
     */
    public function seleccionar(): Response
    {
        // Solo ofertas con estado 'Elegida' o 'Pre-aprobada'
        $ofertas = OfertaCompra::with([
            'proveedor:id,razon_social,cuit',
            'estado:id,nombre'
        ])
        ->whereHas('estado', function($query) {
            $query->whereIn('nombre', ['Elegida', 'Pre-aprobada']);
        })
        ->orderBy('fecha_recepcion', 'desc')
        ->get();

        return Inertia::render('Compras/Ordenes/Seleccionar', [
            'ofertas' => $ofertas,
        ]);
    }

    /**
     * CU-22: Muestra formulario para generar OC desde oferta elegida
     * Kendall P2+P3: Resumen + Ingreso de Motivo
     */
    public function create(Request $request): Response
    {
        $ofertaId = $request->query('oferta_id');
        
        if (!$ofertaId) {
            return redirect()->route('ofertas.index')
                ->with('error', 'Debe seleccionar una oferta para generar la Orden de Compra.');
        }

        $oferta = OfertaCompra::with([
            'proveedor:id,razon_social,cuit,email,telefono,direccion',
            'detalles.producto:id,nombre,codigo',
            'estado:id,nombre',
        ])->findOrFail($ofertaId);

        // Validar que la oferta esté en estado correcto
        if (!in_array($oferta->estado->nombre, ['Elegida', 'Pre-aprobada'])) {
            return redirect()->route('ofertas.show', $oferta->id)
                ->with('error', 'Solo se pueden generar órdenes desde ofertas Elegidas o Pre-aprobadas.');
        }

        return Inertia::render('Compras/Ordenes/Create', [
            'oferta' => $oferta,
        ]);
    }

    /**
     * CU-22: Genera una nueva Orden de Compra desde una oferta elegida
     * 
     * Implementa operación DSS: confirmarGeneracionYEnvio(motivo)
     * 
     * Flujo Principal (pasos CU-22):
     * 1-6. Usuario confirma generación con motivo
     * 7-11. Sistema ejecuta proceso (RegistrarCompraService)
     * 12. Sistema muestra resultado EN LA MISMA VISTA (no redirect)
     * 
     * @return Response Devuelve Create.vue con resultado (éxito/advertencias/error)
     */
    public function store(StoreOrdenCompraRequest $request): Response
    {
        try {
            // Ejecutar CU-22 (pasos 7-11)
            $resultado = $this->registrarCompraService->ejecutar(
                ofertaId: $request->validated('oferta_id'),
                usuarioId: $request->user()->id,
                observaciones: $request->validated('observaciones')
            );

            $orden = $resultado['orden'];
            $advertencias = $resultado['advertencias'];

            // Determinar tipo de resultado según CU-22
            if (empty($advertencias)) {
                // Éxito completo: OC generada y enviada sin problemas
                $tipoResultado = 'success';
                $mensaje = "Orden de Compra {$orden->numero_oc} generada y enviada exitosamente al proveedor.";
            } else {
                // Éxito con advertencias: OC generada pero con excepciones 8a, 9a, 10a u 11a
                $tipoResultado = 'success_with_warnings';
                $mensaje = "Orden de Compra {$orden->numero_oc} generada, pero requiere atención:";
            }

            // CU-22 Paso 12: Confirmar resultado EN LA MISMA VISTA (Kendall: retroalimentación inmediata)
            return Inertia::render('Compras/Ordenes/Create', [
                'oferta' => $request->oferta, // Re-pasar para mostrar resumen
                'resultado' => [
                    'tipo' => $tipoResultado,
                    'mensaje' => $mensaje,
                    'orden' => [
                        'id' => $orden->id,
                        'numero_oc' => $orden->numero_oc,
                        'estado' => $orden->estado->nombre,
                        'total' => $orden->total_final,
                        'proveedor' => $orden->proveedor->razon_social,
                    ],
                    'advertencias' => $advertencias,
                ],
            ]);

        } catch (Exception $e) {
            // Excepción 7a: Error crítico al generar número OC o validar oferta
            Log::error("❌ CU-22 Excepción 7a: " . $e->getMessage());

            return Inertia::render('Compras/Ordenes/Create', [
                'oferta' => OfertaCompra::with(['proveedor', 'detalles.producto', 'estado'])
                    ->find($request->validated('oferta_id')),
                'resultado' => [
                    'tipo' => 'error',
                    'mensaje' => 'No se pudo generar la Orden de Compra',
                    'error' => $e->getMessage(),
                ],
            ]);
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
