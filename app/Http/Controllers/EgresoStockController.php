<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\TipoMovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador: Egresos de Stock
 * 
 * Permite registrar salidas de stock por motivos distintos a venta:
 * - Robo
 * - Pérdida/Merma
 * - Producto defectuoso
 * - Uso interno
 * - Muestra/Donación
 */
class EgresoStockController extends Controller
{
    /**
     * Listado de egresos registrados
     */
    public function index(Request $request): Response
    {
        $query = MovimientoStock::with(['producto', 'tipoMovimiento', 'usuario'])
            ->whereHas('tipoMovimiento', fn($q) => $q->where('signo', -1))
            ->whereNotIn('referenciaTabla', ['ventas', 'detalles_venta']); // Excluir ventas

        // Filtro por tipo
        if ($request->filled('tipo_id')) {
            $query->where('tipo_movimiento_id', $request->tipo_id);
        }

        // Filtro por producto
        if ($request->filled('producto_id')) {
            $query->where('productoID', $request->producto_id);
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $egresos = $query->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        // Tipos de egreso (solo los de signo negativo, excluyendo venta)
        $tiposEgreso = TipoMovimientoStock::where('signo', -1)
            ->where('nombre', 'NOT LIKE', '%Venta%')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Stock/Egresos/Index', [
            'egresos' => $egresos,
            'tiposEgreso' => $tiposEgreso,
            'filters' => $request->only(['tipo_id', 'producto_id', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Formulario para registrar nuevo egreso
     */
    public function create(): Response
    {
        // Productos con stock disponible (no servicios)
        $productos = Producto::with(['stocks' => fn($q) => $q->where('cantidad_disponible', '>', 0)])
            ->where('es_servicio', false)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->whereHas('stocks', fn($q) => $q->where('cantidad_disponible', '>', 0))
            ->select('id', 'nombre', 'codigo')
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'codigo' => $p->codigo,
                    'stock_disponible' => $p->stocks->sum('cantidad_disponible'),
                ];
            });

        // Tipos de egreso (signo negativo, excluyendo venta)
        $tiposEgreso = TipoMovimientoStock::where('signo', -1)
            ->where('nombre', 'NOT LIKE', '%Venta%')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Stock/Egresos/Create', [
            'productos' => $productos,
            'tiposEgreso' => $tiposEgreso,
        ]);
    }

    /**
     * Registrar el egreso de stock
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo_movimiento_id' => 'required|exists:tipos_movimiento_stock,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:500',
        ], [
            'producto_id.required' => 'Debe seleccionar un producto.',
            'tipo_movimiento_id.required' => 'Debe seleccionar el tipo de egreso.',
            'cantidad.required' => 'Debe indicar la cantidad.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'motivo.required' => 'Debe indicar el motivo del egreso.',
        ]);

        try {
            DB::beginTransaction();

            // Obtener stock del producto
            $stock = Stock::where('productoID', $validated['producto_id'])->first();
            
            if (!$stock) {
                return back()->withErrors(['error' => 'El producto no tiene registro de stock.']);
            }

            if ($stock->cantidad_disponible < $validated['cantidad']) {
                return back()->withErrors([
                    'cantidad' => "Stock insuficiente. Disponible: {$stock->cantidad_disponible} unidades."
                ]);
            }

            $stockAnterior = $stock->cantidad_disponible;
            $stockNuevo = $stockAnterior - $validated['cantidad'];

            // Actualizar stock
            $stock->update(['cantidad_disponible' => $stockNuevo]);

            // Registrar movimiento
            MovimientoStock::create([
                'stock_id' => $stock->stock_id,
                'productoID' => $validated['producto_id'],
                'tipo_movimiento_id' => $validated['tipo_movimiento_id'],
                'cantidad' => $validated['cantidad'],
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stockNuevo,
                'motivo' => $validated['motivo'],
                'referenciaTabla' => 'egresos_manuales',
                'referenciaID' => null,
                'user_id' => auth()->id(),
                'fecha_movimiento' => now(),
            ]);

            DB::commit();

            return redirect()->route('egresos-stock.index')
                ->with('success', 'Egreso de stock registrado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar egreso de stock: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al registrar el egreso: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver detalle de un egreso
     */
    public function show(MovimientoStock $egreso): Response
    {
        $egreso->load(['producto', 'tipoMovimiento', 'usuario']);

        return Inertia::render('Stock/Egresos/Show', [
            'egreso' => $egreso,
        ]);
    }
}
