<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compras\StoreOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\CotizacionProveedor;
use App\Models\EstadoOrdenCompra;
use App\Repositories\OrdenCompraRepository;
use App\Services\Compras\RegistrarCompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Exception;

/**
 * Controlador de Órdenes de Compra (CU-22)
 * 
 * MODELO SIMPLIFICADO (sin tabla ofertas_compra):
 * SolicitudCotizacion → CotizacionProveedor (elegida) → OrdenCompra
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
     * CU-22 Pantalla 1: Punto de Partida - Listado de Cotizaciones Elegidas
     * 
     * Contexto: El administrador necesita encontrar las cotizaciones que han sido
     * seleccionadas como ganadoras y que están listas para convertirse en OC.
     * 
     * Principio K&K (Salida de Navegación): Lista filtrada "To-do list"
     */
    public function index(Request $request): Response
    {
        // Query base: cotizaciones marcadas como elegidas sin OC generada
        $query = CotizacionProveedor::with([
            'proveedor:id,razon_social,cuit',
            'solicitud:id,codigo_solicitud,fecha_vencimiento',
            'respuestas.producto:id,nombre,codigo',
        ])
        ->where('elegida', true)
        ->whereDoesntHave('ordenCompra'); // Solo las que NO tienen OC aún

        // Filtro por proveedor
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        // Búsqueda por código de solicitud
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->whereHas('solicitud', function($sq) use ($busqueda) {
                    $sq->where('codigo_solicitud', 'LIKE', "%{$busqueda}%");
                })
                ->orWhereHas('proveedor', function($pq) use ($busqueda) {
                    $pq->where('razon_social', 'LIKE', "%{$busqueda}%");
                });
            });
        }

        // Ordenar y paginar
        $cotizaciones = $query->orderBy('fecha_respuesta', 'desc')
                              ->paginate(10)
                              ->withQueryString();

        // Obtener proveedores con cotizaciones elegidas para el filtro
        $proveedores = \App\Models\Proveedor::whereHas('cotizacionesProveedor', function($q) {
            $q->where('elegida', true)->whereDoesntHave('ordenCompra');
        })->select('id', 'razon_social')->orderBy('razon_social')->get();

        return Inertia::render('Compras/Ordenes/Index', [
            'cotizaciones' => $cotizaciones,
            'proveedores' => $proveedores,
            'filters' => $request->only(['proveedor_id', 'busqueda']),
        ]);
    }

    /**
     * CU-24: Consultar Órdenes de Compra
     * 
     * Contexto: El administrador necesita consultar todas las OC generadas
     * para hacer seguimiento de las compras.
     * 
     * Principio K&K (Salida de Navegación): Lista con filtros y búsqueda
     */
    public function historial(Request $request): Response
    {
        // Query base: todas las órdenes de compra
        $query = OrdenCompra::with([
            'proveedor:id,razon_social,cuit',
            'estado:id,nombre',
            'usuario:id,name',
        ]);

        // Filtro por proveedor
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        // Filtro por estado
        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        // Búsqueda por número de OC
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_oc', 'LIKE', "%{$busqueda}%")
                  ->orWhereHas('proveedor', function($pq) use ($busqueda) {
                      $pq->where('razon_social', 'LIKE', "%{$busqueda}%");
                  });
            });
        }

        // Ordenar por fecha más reciente y paginar
        $ordenes = $query->orderBy('fecha_emision', 'desc')
                        ->paginate(15)
                        ->withQueryString();

        // Obtener proveedores para el filtro
        $proveedores = \App\Models\Proveedor::whereHas('ordenesCompra')
            ->select('id', 'razon_social')
            ->orderBy('razon_social')
            ->get();

        // Obtener estados para el filtro
        $estados = EstadoOrdenCompra::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Compras/Ordenes/Historial', [
            'ordenes' => $ordenes,
            'proveedores' => $proveedores,
            'estados' => $estados,
            'filters' => $request->only(['proveedor_id', 'estado_id', 'fecha_desde', 'fecha_hasta', 'busqueda']),
        ]);
    }

    /**
     * CU-22: Muestra formulario para generar OC desde cotización elegida
     * Kendall P2+P3: Resumen + Ingreso de Motivo
     */
    public function create(Request $request): Response
    {
        $cotizacionId = $request->query('cotizacion_id');
        
        if (!$cotizacionId) {
            return redirect()->route('ordenes.index')
                ->with('error', 'Debe seleccionar una cotización para generar la Orden de Compra.');
        }

        $cotizacion = CotizacionProveedor::with([
            'proveedor:id,razon_social,cuit,telefono,email,direccion_id',
            'proveedor.direccion',
            'solicitud:id,codigo_solicitud,fecha_vencimiento,observaciones',
            'respuestas.producto:id,nombre,codigo',
        ])->findOrFail($cotizacionId);

        // Validar que la cotización esté elegida
        if (!$cotizacion->elegida) {
            return redirect()->route('solicitudes-cotizacion.show', $cotizacion->solicitud_id)
                ->with('error', 'Solo se pueden generar órdenes desde cotizaciones elegidas.');
        }

        // Validar que no tenga ya una OC
        if ($cotizacion->ordenCompra()->exists()) {
            return redirect()->route('ordenes.historial')
                ->with('error', 'Esta cotización ya tiene una Orden de Compra generada.');
        }

        return Inertia::render('Compras/Ordenes/Create', [
            'cotizacion' => $cotizacion,
        ]);
    }

    /**
     * CU-22: Genera una nueva Orden de Compra desde cotización elegida
     * 
     * Implementa operación DSS: confirmarGeneracionYEnvio(motivo)
     * 
     * Flujo Principal:
     * 1-6. Usuario confirma generación con motivo
     * 7-11. Sistema ejecuta proceso (RegistrarCompraService)
     * 12. Sistema muestra resultado EN LA MISMA VISTA
     * 
     * @return Response Devuelve Create.vue con resultado (éxito/advertencias/error)
     */
    public function store(StoreOrdenCompraRequest $request): Response
    {
        try {
            // Ejecutar CU-22
            $resultado = $this->registrarCompraService->ejecutar(
                cotizacionId: $request->validated('cotizacion_id'),
                usuarioId: $request->user()->id,
                observaciones: $request->validated('motivo')
            );

            $orden = $resultado['orden'];
            $advertencias = $resultado['advertencias'];

            // Determinar tipo de resultado
            if (empty($advertencias)) {
                $tipoResultado = 'success';
                $mensaje = "Orden de Compra {$orden->numero_oc} generada y enviada exitosamente al proveedor.";
            } else {
                $tipoResultado = 'success_with_warnings';
                $mensaje = "Orden de Compra {$orden->numero_oc} generada, pero requiere atención:";
            }

            // Devolver resultado EN LA MISMA VISTA
            return Inertia::render('Compras/Ordenes/Create', [
                'cotizacion' => CotizacionProveedor::with([
                    'proveedor', 'solicitud', 'respuestas.producto'
                ])->find($request->validated('cotizacion_id')),
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
            Log::error("❌ CU-22 Error: " . $e->getMessage());

            return Inertia::render('Compras/Ordenes/Create', [
                'cotizacion' => CotizacionProveedor::with(['proveedor', 'solicitud', 'respuestas.producto'])
                    ->find($request->validated('cotizacion_id')),
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
     * Incluye historial de recepciones
     */
    public function show(int $id): Response
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'cotizacionProveedor.solicitud',
            'detalles.producto',
            'estado',
            'usuario',
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

    /**
     * Formulario para crear OC directa (sin cotización previa)
     * 
     * Caso de uso: Compras urgentes o de proveedores conocidos
     * donde no se requiere el proceso de cotización.
     */
    public function createDirecto(): Response
    {
        // Proveedores activos
        $proveedores = \App\Models\Proveedor::where('activo', true)
            ->select('id', 'razon_social', 'cuit', 'email', 'whatsapp')
            ->orderBy('razon_social')
            ->get();

        // Productos activos (no servicios)
        $productos = \App\Models\Producto::with(['stocks'])
            ->where('es_servicio', false)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->select('id', 'nombre', 'codigo')
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'codigo' => $p->codigo,
                    'stock_actual' => $p->stocks->sum('cantidad'),
                ];
            });

        return Inertia::render('Compras/Ordenes/CreateDirecto', [
            'proveedores' => $proveedores,
            'productos' => $productos,
        ]);
    }

    /**
     * Genera OC directa sin cotización
     */
    public function storeDirecto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.min' => 'Debe agregar al menos un producto.',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Generar número de OC
            $numeroOC = OrdenCompra::generarNumeroOC();

            // Calcular total
            $total = collect($validated['productos'])->sum(fn($p) => $p['cantidad'] * $p['precio_unitario']);

            // Crear OC (sin cotización) - Estado inicial: Enviada
            $orden = OrdenCompra::create([
                'numero_oc' => $numeroOC,
                'proveedor_id' => $validated['proveedor_id'],
                'cotizacion_proveedor_id' => null, // Sin cotización
                'user_id' => $request->user()->id,
                'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIADA),
                'total_final' => $total,
                'fecha_emision' => now(),
                'observaciones' => $validated['observaciones'] ?? 'Orden directa (sin cotización previa)',
            ]);

            // Crear detalles
            foreach ($validated['productos'] as $item) {
                \App\Models\DetalleOrdenCompra::create([
                    'orden_compra_id' => $orden->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad_pedida' => $item['cantidad'],
                    'cantidad_recibida' => 0,
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            // Regenerar PDF (usa método existente)
            $this->registrarCompraService->regenerarPdf($orden);
            
            // Intentar enviar por WhatsApp si tiene número
            $orden->load('proveedor');
            if ($orden->proveedor->whatsapp) {
                try {
                    $this->registrarCompraService->reenviarWhatsApp($orden);
                } catch (\Exception $e) {
                    Log::warning("No se pudo enviar WhatsApp para OC {$orden->numero_oc}: " . $e->getMessage());
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('ordenes.show', $orden->id)
                ->with('success', "Orden de Compra {$orden->numero_oc} creada y enviada al proveedor.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Error al crear OC directa: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }
}
