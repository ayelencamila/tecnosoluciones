<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compras\StoreOfertaRequest;
use App\Services\Compras\RegistrarOfertaService;
use App\Repositories\OfertaCompraRepository;
use App\Models\OfertaCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Auditoria;
use App\Models\EstadoOferta;
use App\Models\SolicitudCotizacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfertaCompraController extends Controller
{
    public function __construct(
        protected OfertaCompraRepository $ofertaCompraRepository
    ) {}
    /**
     * CU-21 (Paso 1): Listar ofertas para gestión
     * MEJORA: Usa Repository para consultas complejas
     */
    public function index(Request $request): Response
    {
        // Delegar al repository (Patrón Repository - Sommerville)
        $ofertas = $this->ofertaCompraRepository->filtrar(
            criterios: $request->only(['search', 'estado_id', 'proveedor_id']),
            perPage: 10
        );

        // Productos con múltiples ofertas para comparar
        $productosConOfertas = $this->ofertaCompraRepository->productosConMultiplesOfertas(limite: 10);

        return Inertia::render('Compras/Ofertas/Index', [
            'ofertas' => $ofertas,
            'filters' => $request->only(['search']),
            'productosConOfertas' => $productosConOfertas,
        ]);
    }

    /**
     * CU-21 (Paso 2): Formulario de registro de oferta
     */
    public function create(Request $request): Response
    {
        // Si viene de una solicitud (CU-20), precargamos datos
        $solicitudId = $request->query('solicitud_id');
        
        // Obtenemos el ID del estado "Activo" para productos
        $estadoActivo = \App\Models\EstadoProducto::where('nombre', 'Activo')->first();
        
        return Inertia::render('Compras/Ofertas/Create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
            // Enviamos productos activos para el selector del array dinámico
            'productos' => Producto::when($estadoActivo, fn($q) => $q->where('estadoProductoID', $estadoActivo->id))
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get(),
            'solicitud_id' => $solicitudId,
        ]);
    }

    /**
     * CU-21 (Paso 14): Procesar y guardar la oferta
     */
    public function store(StoreOfertaRequest $request, RegistrarOfertaService $service): RedirectResponse
    {
        try {
            $oferta = $service->ejecutar($request->validated(), $request->user()->id);

            return redirect()->route('ofertas.show', $oferta->id)
                ->with('success', "Oferta {$oferta->codigo_oferta} registrada correctamente.");

        } catch (\Exception $e) {
            Log::error("Error al registrar oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Ocurrió un error inesperado al registrar la oferta. ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * CU-21 (Paso 11): Ver detalle y comparar
     */
    public function show($id): Response
    {
        $oferta = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado', 'user', 'solicitud'])
            ->findOrFail($id);

        return Inertia::render('Compras/Ofertas/Show', [
            'oferta' => $oferta
        ]);
    }

    /**
     * CU-21 (Paso 10): Vista de comparación de ofertas
     * MEJORA: Usa Repository para query de comparación
     */
    public function comparar(Request $request): Response
    {
        $productoId = $request->query('producto_id');
        
        if (!$productoId) {
            return redirect()->route('ofertas.index')
                ->with('info', 'Seleccione un producto para comparar ofertas.');
        }

        $producto = Producto::findOrFail($productoId);

        // CU-21 Paso 10: Obtener ofertas ordenadas por precio y plazo
        $ofertas = $this->ofertaCompraRepository->ofertasParaComparar($productoId);

        // Excepción 10a: Comparación no significativa si hay <= 1 oferta
        $comparacionSignificativa = $ofertas->count() > 1;

        return Inertia::render('Compras/Ofertas/Comparar', [
            'producto' => $producto,
            'ofertas' => $ofertas,
            'filters' => $request->only(['producto_id']),
            'comparacionSignificativa' => $comparacionSignificativa,
        ]);
    }

    /**
     * CU-21 (Paso 12): Elegir/Pre-aprobar una oferta
     * El Gerente selecciona la oferta ganadora
     */
    public function elegir(OfertaCompra $oferta): RedirectResponse
    {
        try {
            DB::transaction(function () use ($oferta) {
                // Marcar como elegida usando el método del modelo
                $oferta->elegir();

                // Registrar auditoría (Paso 13)
                Auditoria::create([
                    'accion' => Auditoria::ACCION_ELEGIR_OFERTA,
                    'tabla_afectada' => 'ofertas_compra',
                    'registro_id' => $oferta->id,
                    'user_id' => auth()->id(),
                    'motivo' => 'Oferta seleccionada para generar orden de compra',
                    'detalles_json' => json_encode([
                        'codigo_oferta' => $oferta->codigo_oferta,
                        'proveedor_id' => $oferta->proveedor_id,
                        'proveedor_nombre' => $oferta->proveedor->razon_social,
                        'total_estimado' => $oferta->total_estimado,
                        'estado_anterior' => 'Pendiente',
                        'estado_nuevo' => 'Elegida',
                    ]),
                    'fecha' => now(),
                ]);
            });

            return redirect()->back()
                ->with('success', "Oferta {$oferta->codigo_oferta} elegida correctamente. Ya puede generar la Orden de Compra.");

        } catch (\Exception $e) {
            Log::error("Error al elegir oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'No se pudo elegir la oferta: ' . $e->getMessage()]);
        }
    }

    /**
     * CU-21: Rechazar una oferta
     */
    public function rechazar(Request $request, OfertaCompra $oferta): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($oferta, $request) {
                $estadoAnterior = $oferta->estado->nombre;
                
                $oferta->rechazar($request->motivo);

                Auditoria::create([
                    'accion' => 'RECHAZAR_OFERTA',
                    'tabla_afectada' => 'ofertas_compra',
                    'registro_id' => $oferta->id,
                    'user_id' => auth()->id(),
                    'motivo' => $request->motivo,
                    'detalles_json' => json_encode([
                        'codigo_oferta' => $oferta->codigo_oferta,
                        'proveedor_id' => $oferta->proveedor_id,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => 'Rechazada',
                    ]),
                    'fecha' => now(),
                ]);
            });

            return redirect()->back()
                ->with('success', "Oferta {$oferta->codigo_oferta} rechazada.");

        } catch (\Exception $e) {
            Log::error("Error al rechazar oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'No se pudo rechazar la oferta: ' . $e->getMessage()]);
        }
    }

    /**
     * CU-21 Excepción 12a: Cancelar evaluación de ofertas
     * El usuario decide no seleccionar ninguna oferta en ese momento.
     * Las ofertas quedan en estado "Pendiente" para futuras gestiones.
     */
    public function cancelarEvaluacion(Request $request): RedirectResponse
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            // Registrar la cancelación en auditoría
            Auditoria::create([
                'accion' => 'CANCELAR_EVALUACION_OFERTAS',
                'tabla_afectada' => 'ofertas_compra',
                'registro_id' => null,
                'user_id' => auth()->id(),
                'motivo' => $request->motivo ?? 'Evaluación cancelada por el usuario',
                'detalles_json' => json_encode([
                    'producto_id' => $request->producto_id,
                    'accion' => 'El usuario canceló la evaluación sin seleccionar ninguna oferta',
                ]),
                'fecha' => now(),
            ]);

            return redirect()->route('ofertas.index')
                ->with('info', 'Evaluación cancelada. Las ofertas permanecen en estado pendiente para futuras gestiones.');

        } catch (\Exception $e) {
            Log::error("Error al cancelar evaluación: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Error al registrar la cancelación: ' . $e->getMessage()]);
        }
    }
}