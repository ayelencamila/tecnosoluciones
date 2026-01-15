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
     * Listar recepciones de mercadería
     */
    public function index(Request $request): Response
    {
        $query = RecepcionMercaderia::with([
            'ordenCompra:id,numero_oc,proveedor_id',
            'ordenCompra.proveedor:id,nombre',
            'usuario:id,name',
        ])
            ->latest('fecha_recepcion');

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

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_recepcion', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_recepcion', '<=', $request->fecha_hasta);
        }

        $recepciones = $query->paginate(15)->withQueryString();

        return Inertia::render('Compras/Recepciones/Index', [
            'recepciones' => $recepciones,
            'filters' => $request->only(['numero_recepcion', 'orden_compra_id', 'tipo', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Mostrar formulario para crear recepción
     * Se accede desde la vista de una OC específica
     */
    public function create(Request $request): Response
    {
        $ordenCompraId = $request->query('orden_compra_id');

        if (!$ordenCompraId) {
            abort(404, 'Debe especificar una Orden de Compra');
        }

        // Obtener IDs de los estados permitidos para recepcionar
        $estadosPermitidos = [
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::CONFIRMADA),
            EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::RECIBIDA_PARCIAL),
        ];

        $ordenCompra = OrdenCompra::with([
            'proveedor:id,nombre,cuit',
            'detalles.producto:id,nombre,codigo,unidad_medida',
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
            'ordenCompra.proveedor:id,nombre,cuit',
            'usuario:id,name',
            // producto se obtiene vía detalleOrden (3FN)
            'detalles.detalleOrden:id,producto_id,cantidad_pedida,precio_unitario',
            'detalles.detalleOrden.producto:id,nombre,codigo,unidad_medida',
        ]);

        return Inertia::render('Compras/Recepciones/Show', [
            'recepcion' => $recepcion,
        ]);
    }
}
