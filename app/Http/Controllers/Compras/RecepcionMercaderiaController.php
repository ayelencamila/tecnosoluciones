<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compras\StoreRecepcionRequest;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\RecepcionMercaderia;
use App\Services\Compras\RecepcionarMercaderiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CU-23: Recepcionar Mercadería
 */
class RecepcionMercaderiaController extends Controller
{
    public function __construct(
        private RecepcionarMercaderiaService $recepcionarMercaderiaService
    ) {}

    /**
     * CU-23 Pantalla 1: Seleccionar Orden de Compra Pendiente
     * 
     * Objetivo: Que el usuario de depósito localice rápidamente la OC digital
     * que coincide con los papeles o la mercadería física que tiene enfrente.
     * 
     * Principio K&K (Diseño de Salida/Navegación): Formato tabular con filtro
     * predeterminado por estados válidos para recibir ("Enviada", "Recibida Parcial").
     */
    public function index(Request $request): Response
    {
        // Obtener estados permitidos para recepcionar (configurables desde BD)
        $nombresEstadosPermitidos = [EstadoOrdenCompra::ENVIADA, EstadoOrdenCompra::RECIBIDA_PARCIAL];
        $estadosPermitidos = EstadoOrdenCompra::whereIn('nombre', $nombresEstadosPermitidos)->get();
        $idsEstadosPermitidos = $estadosPermitidos->pluck('id');

        $query = OrdenCompra::with([
            'proveedor:id,razon_social,cuit',
            'estado:id,nombre',
            'detalles.producto:id,nombre,codigo',
        ]);

        // Filtro predeterminado: solo OC pendientes de recepción
        $filtroEstado = $request->input('filtro_estado', 'pendientes');
        if ($filtroEstado === 'pendientes') {
            $query->whereIn('estado_id', $idsEstadosPermitidos);
        } elseif ($filtroEstado !== 'todos') {
            // Filtrar por estado específico (por ID)
            $query->where('estado_id', $filtroEstado);
        }

        // Búsqueda por número de OC o proveedor
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_oc', 'LIKE', "%{$busqueda}%")
                  ->orWhereHas('proveedor', function($pq) use ($busqueda) {
                      $pq->where('razon_social', 'LIKE', "%{$busqueda}%")
                         ->orWhere('cuit', 'LIKE', "%{$busqueda}%");
                  });
            });
        }

        // Ordenar por fecha de emisión más reciente
        $ordenes = $query->orderBy('fecha_emision', 'desc')
                        ->paginate(15)
                        ->withQueryString();

        // Agregar cantidad de items pendientes a cada orden
        $ordenes->getCollection()->transform(function ($orden) {
            $orden->items_pendientes = $orden->detalles->sum(function ($detalle) {
                return max(0, $detalle->cantidad_pedida - ($detalle->cantidad_recibida ?? 0));
            });
            return $orden;
        });

        // Obtener proveedores para el filtro (solo los que tienen OC pendientes)
        $proveedores = \App\Models\Proveedor::whereHas('ordenesCompra', function($q) use ($idsEstadosPermitidos) {
            $q->whereIn('estado_id', $idsEstadosPermitidos);
        })->select('id', 'razon_social')->orderBy('razon_social')->get();

        return Inertia::render('Compras/Recepciones/Index', [
            'ordenes' => $ordenes,
            'proveedores' => $proveedores,
            'estadosPermitidos' => $estadosPermitidos, // Estados desde BD
            'filters' => $request->only(['busqueda', 'filtro_estado']),
        ]);
    }

    /**
     * CU-23 Pantalla 2: Formulario de Registro de Recepción
     * 
     * Se accede desde la pantalla de selección de OC (Index).
     * Solo permite recepcionar OC en estado "Enviada" o "Recibida Parcial".
     */
    public function create(Request $request): Response
    {
        $ordenCompraId = $request->query('orden_compra_id');

        if (!$ordenCompraId) {
            abort(404, 'Debe especificar una Orden de Compra');
        }

        // Obtener IDs de los estados permitidos para recepcionar
        // (Enviada = pendiente de recepción, Recibida Parcial = continuar recepción)
        $estadosPermitidos = [
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::ENVIADA),
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
        ];

        $ordenCompra = OrdenCompra::with([
            'proveedor:id,razon_social,cuit',
            'detalles.producto:id,nombre,codigo',
        ])
            ->whereIn('estado_id', $estadosPermitidos)
            ->findOrFail($ordenCompraId);

        // Calcular cantidades pendientes para cada detalle
        $detallesConPendientes = $ordenCompra->detalles->map(function ($detalle) {
            return [
                'id' => $detalle->id,
                'producto_id' => $detalle->producto_id,
                'producto' => $detalle->producto,
                'cantidad_solicitada' => $detalle->cantidad_pedida,
                'cantidad_recibida' => $detalle->cantidad_recibida ?? 0,
                'cantidad_pendiente' => $detalle->cantidad_pedida - ($detalle->cantidad_recibida ?? 0),
                'precio_unitario' => $detalle->precio_unitario,
            ];
        })->filter(fn($d) => $d['cantidad_pendiente'] > 0);

        return Inertia::render('Compras/Recepciones/Create', [
            'ordenCompra' => [
                'id' => $ordenCompra->id,
                'numero_orden' => $ordenCompra->numero_oc,
                'proveedor' => $ordenCompra->proveedor,
                'fecha_orden' => $ordenCompra->fecha_emision,
                'detalles' => $detallesConPendientes->values(),
            ],
        ]);
    }

    /**
     * Procesar la recepción de mercadería
     */
    public function store(StoreRecepcionRequest $request): RedirectResponse
    {
        $recepcion = $this->recepcionarMercaderiaService->ejecutar(
            ordenCompraId: $request->orden_compra_id,
            items: $request->items,
            observaciones: $request->observaciones,
            usuarioId: auth()->id()
        );

        return redirect()
            ->route('recepciones.show', $recepcion)
            ->with('success', "Recepción {$recepcion->numero_recepcion} registrada exitosamente.");
    }

    /**
     * Mostrar detalle de una recepción
     */
    public function show(RecepcionMercaderia $recepcion): Response
    {
        $recepcion->load([
            'ordenCompra:id,numero_oc,proveedor_id,fecha_emision',
            'ordenCompra.proveedor:id,razon_social,cuit',
            'usuario:id,name',
            // producto se obtiene vía detalleOrden (3FN)
            'detalles.detalleOrden:id,producto_id,cantidad_pedida,precio_unitario',
            'detalles.detalleOrden.producto:id,nombre,codigo',
        ]);

        return Inertia::render('Compras/Recepciones/Show', [
            'recepcion' => $recepcion,
        ]);
    }

    /**
     * CU-23: Historial de recepciones de mercadería
     * 
     * Muestra el registro completo de todas las recepciones realizadas,
     * permitiendo filtrar por número, tipo y rango de fechas.
     */
    public function historial(Request $request): Response
    {
        $query = RecepcionMercaderia::with([
            'ordenCompra:id,numero_oc,proveedor_id',
            'ordenCompra.proveedor:id,razon_social',
            'proveedor:id,razon_social',
            'usuario:id,name',
        ])->latest('fecha_recepcion');

        // Filtro por número de recepción
        if ($request->filled('numero_recepcion')) {
            $query->where('numero_recepcion', 'like', '%' . $request->numero_recepcion . '%');
        }

        // Filtro por orden de compra
        if ($request->filled('orden_compra_id')) {
            $query->where('orden_compra_id', $request->orden_compra_id);
        }

        // Filtro por tipo de recepción
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Filtro por origen
        if ($request->filled('origen')) {
            $query->where('origen', $request->origen);
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_recepcion', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_recepcion', '<=', $request->fecha_hasta);
        }

        $recepciones = $query->paginate(15)->withQueryString();

        return Inertia::render('Compras/Recepciones/Historial', [
            'recepciones' => $recepciones,
            'filters' => $request->only(['numero_recepcion', 'orden_compra_id', 'tipo', 'origen', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Formulario para recepción directa (sin OC)
     */
    public function createDirecto(): Response
    {
        $proveedores = \App\Models\Proveedor::where('activo', true)
            ->select('id', 'razon_social', 'cuit')
            ->orderBy('razon_social')
            ->get();

        $productos = \App\Models\Producto::where('es_servicio', false)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->select('id', 'nombre', 'codigo')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Compras/Recepciones/CreateDirecto', [
            'proveedores' => $proveedores,
            'productos' => $productos,
        ]);
    }

    /**
     * Guardar recepción directa (sin OC)
     */
    public function storeDirecto(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
            'observaciones' => 'required|string|max:500',
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'items.required' => 'Debe agregar al menos un producto.',
            'observaciones.required' => 'Debe indicar el motivo de la recepcion.',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Crear recepción
            $recepcion = RecepcionMercaderia::create([
                'numero_recepcion' => RecepcionMercaderia::generarNumeroRecepcion(),
                'orden_compra_id' => null,
                'proveedor_id' => $validated['proveedor_id'],
                'user_id' => auth()->id(),
                'fecha_recepcion' => now(),
                'observaciones' => $validated['observaciones'],
                'tipo' => 'total',
                'origen' => RecepcionMercaderia::ORIGEN_COMPRA_DIRECTA,
            ]);

            // Crear detalles y actualizar stock
            foreach ($validated['items'] as $item) {
                // Crear detalle de recepción
                \App\Models\DetalleRecepcion::create([
                    'recepcion_id' => $recepcion->id,
                    'detalle_orden_id' => null,
                    'producto_id' => $item['producto_id'],
                    'cantidad_recibida' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'] ?? null,
                ]);

                // Actualizar stock del producto
                $stock = \App\Models\Stock::where('productoID', $item['producto_id'])->first();
                
                if ($stock) {
                    $stockAnterior = $stock->cantidad_disponible;
                    $stockNuevo = $stockAnterior + $item['cantidad'];

                    $stock->update(['cantidad_disponible' => $stockNuevo]);

                    // Registrar movimiento de stock
                    $tipoEntrada = \App\Models\TipoMovimientoStock::where('signo', 1)
                        ->where('nombre', 'LIKE', '%Compra%')
                        ->first();

                    \App\Models\MovimientoStock::create([
                        'stock_id' => $stock->stock_id,
                        'productoID' => $item['producto_id'],
                        'tipo_movimiento_id' => $tipoEntrada?->id,
                        'cantidad' => $item['cantidad'],
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo' => $stockNuevo,
                        'motivo' => "Recepcion directa: {$recepcion->numero_recepcion}",
                        'referenciaTabla' => 'recepciones_mercaderia',
                        'referenciaID' => $recepcion->id,
                        'user_id' => auth()->id(),
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('recepciones.historial')
                ->with('success', "Recepcion {$recepcion->numero_recepcion} registrada exitosamente.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error en recepcion directa: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }
}
