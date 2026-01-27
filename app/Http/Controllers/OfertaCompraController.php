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
     * CU-21 (Paso 1 y 2): Panel Principal - Listado de Solicitudes Pendientes
     * 
     * Diseño K&K: "Tablero de control" - Lista de tareas pendientes
     * Muestra solicitudes de cotización que necesitan gestión de ofertas
     */
    public function index(Request $request): Response
    {
        // CU-21 Paso 2: Solicitudes de cotización pendientes de gestión
        // Incluye las que tienen respuestas de proveedores sin convertir a ofertas formales
        $query = SolicitudCotizacion::with([
            'estado:id,nombre',
            'detalles.producto:id,nombre,codigo',
        ])
        ->withCount(['cotizacionesProveedores as ofertas_count' => function($q) {
            $q->whereNotNull('fecha_respuesta');
        }]);

        // Filtrar por estado
        if ($request->filled('estado')) {
            if ($request->estado === 'pendientes') {
                // Filtro especial: solo estados que requieren gestión de ofertas
                $query->whereHas('estado', fn($q) => $q->where('requiere_gestion_ofertas', true));
            } else {
                // Filtro por estado específico
                $query->whereHas('estado', fn($q) => $q->where('nombre', $request->estado));
            }
        }
        // Si no hay filtro (Todos los Estados), no aplica filtro → muestra todas

        // Filtrar por búsqueda de producto
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_solicitud', 'like', "%{$search}%")
                  ->orWhereHas('detalles.producto', function($q2) use ($search) {
                      $q2->where('nombre', 'like', "%{$search}%")
                         ->orWhere('codigo', 'like', "%{$search}%");
                  });
            });
        }

        $solicitudesPendientes = $query->latest()->paginate(10)->withQueryString();

        // CU-21 Paso 10: Productos con múltiples ofertas para comparar
        $productosConOfertas = $this->ofertaCompraRepository->productosConMultiplesOfertas(limite: 10);

        // Estados de solicitud para el filtro dropdown (todos los activos, ordenados)
        $estadosSolicitud = \App\Models\EstadoSolicitud::activos()
            ->ordenados()
            ->select('id', 'nombre')
            ->get();

        // Contadores para resumen (usando campo parametrizable)
        $counts = [
            'solicitudes_pendientes' => SolicitudCotizacion::whereHas('estado', fn($q) => $q->where('requiere_gestion_ofertas', true))->count(),
            'ofertas_registradas' => OfertaCompra::count(),
            'ofertas_elegidas' => OfertaCompra::whereHas('estado', fn($q) => $q->where('nombre', 'Elegida'))->count(),
        ];

        return Inertia::render('Compras/Ofertas/Index', [
            'solicitudesPendientes' => $solicitudesPendientes,
            'estadosSolicitud' => $estadosSolicitud,
            'productosConOfertas' => $productosConOfertas,
            'filters' => $request->only(['search', 'estado']),
            'counts' => $counts,
        ]);
    }

    /**
     * CU-21 (Paso 2): Formulario de registro de oferta
     * CORRECCIÓN: Si viene de solicitud, PRE-CARGA los datos de la cotización
     */
    public function create(Request $request): Response
    {
        $solicitudId = $request->query('solicitud_id');
        $cotizacionId = $request->query('cotizacion_id');
        
        // Obtenemos el ID del estado "Activo" para productos
        $estadoActivo = \App\Models\EstadoProducto::where('nombre', 'Activo')->first();
        
        $datosPrecargados = null;
        $solicitud = null;
        
        // SI VIENE DE SOLICITUD → CARGAR datos de la solicitud (Producto + Cantidad precargados)
        if ($solicitudId) {
            $solicitud = SolicitudCotizacion::with([
                'detalles.producto:id,nombre,codigo',
                'estado:id,nombre',
            ])->find($solicitudId);
        }
        
        // SI VIENE DE COTIZACIÓN → PRE-CARGAR DATOS (Kendall: reutilizar info existente)
        if ($cotizacionId) {
            $cotizacion = \App\Models\CotizacionProveedor::with([
                'proveedor:id,razon_social,email',
                'respuestas.producto:id,nombre,codigo',
                'solicitud:id,codigo_solicitud,fecha_emision'
            ])->findOrFail($cotizacionId);
            
            $datosPrecargados = [
                'proveedor_id' => $cotizacion->proveedor_id,
                'proveedor' => $cotizacion->proveedor,
                'fecha_recepcion' => $cotizacion->fecha_respuesta ? \Carbon\Carbon::parse($cotizacion->fecha_respuesta)->format('Y-m-d') : now()->format('Y-m-d'),
                'items' => $cotizacion->respuestas->map(function($respuesta) {
                    return [
                        'producto_id' => $respuesta->producto_id,
                        'producto' => $respuesta->producto,
                        'cantidad' => $respuesta->cantidad_solicitada ?? 1,
                        'precio_unitario' => $respuesta->precio_unitario ?? 0,
                        'disponibilidad_inmediata' => $respuesta->disponibilidad_inmediata ?? true,
                        'dias_entrega' => $respuesta->dias_entrega ?? 0,
                    ];
                })->toArray(),
                'observaciones' => "Oferta basada en cotización " . ($cotizacion->solicitud->codigo_solicitud ?? ''),
            ];
        }
        
        return Inertia::render('Compras/Ofertas/Create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
            'productos' => Producto::when($estadoActivo, fn($q) => $q->where('estadoProductoID', $estadoActivo->id))
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get(),
            'solicitud_id' => $solicitudId,
            'solicitud' => $solicitud,
            'cotizacion_id' => $cotizacionId,
            'datosPrecargados' => $datosPrecargados,
        ]);
    }

    /**
     * CU-21 (Pasos 8 → 10): Procesar, guardar y redirigir según DSS
     * Contrato DSS: registrarOferta() debe devolver comparativaOfertas si hay múltiples
     */
    public function store(StoreOfertaRequest $request, RegistrarOfertaService $service): RedirectResponse
    {
        try {
            $oferta = $service->ejecutar($request->validated(), $request->user()->id);

            // DSS: Después de registrar, verificar si hay múltiples ofertas para comparar
            $primerDetalle = $oferta->detalles->first();
            
            if ($primerDetalle) {
                $productoId = $primerDetalle->producto_id;
                
                // Contar ofertas pendientes/pre-aprobadas para este producto
                $estadosPendientes = EstadoOferta::whereIn('nombre', ['Pendiente', 'Pre-aprobada'])->pluck('id');
                
                $cantidadOfertas = OfertaCompra::whereHas('detalles', function($q) use ($productoId) {
                    $q->where('producto_id', $productoId);
                })
                ->whereIn('estado_id', $estadosPendientes)
                ->count();
                
                // CU-21 Paso 10: Si hay múltiples ofertas, mostrar comparativa (cumple DSS)
                if ($cantidadOfertas > 1) {
                    return redirect()->route('ofertas.comparar', ['producto_id' => $productoId])
                        ->with('success', "Oferta {$oferta->codigo_oferta} registrada. Comparando con otras ofertas del mismo producto...");
                }
            }
            
            // Excepción 10a: Si es la única oferta, ir directo al detalle (sin comparativa formal)
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
     * CU-21 (Paso 12): Vista de Confirmación de Selección (Kendall: Vista de Control)
     * Separada de Show para cumplir principio de separación de responsabilidades
     */
    public function confirmarSeleccion($id): Response|RedirectResponse
    {
        $oferta = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado', 'user'])
            ->findOrFail($id);

        // Validar que la oferta puede ser elegida
        if (!in_array($oferta->estado->nombre, ['Pendiente', 'Pre-aprobada'])) {
            return redirect()->route('ofertas.show', $id)
                ->with('error', 'Esta oferta no puede ser seleccionada en su estado actual.');
        }

        return Inertia::render('Compras/Ofertas/ConfirmarSeleccion', [
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