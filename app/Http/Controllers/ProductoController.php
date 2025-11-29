<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;

// Modelos
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\Marca;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\Stock; 
use App\Models\MovimientoStock; 
use App\Models\Auditoria; 
use App\Models\EstadoCuentaCorriente; 

// Requests
use App\Http\Requests\Productos\StoreProductoRequest;
use App\Http\Requests\Productos\UpdateProductoRequest;
use App\Http\Requests\Productos\DarDeBajaProductoRequest;

// Servicios
use App\Services\Productos\RegistrarProductoService;
use App\Services\Productos\UpdateProductoService;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::query()
            ->with(['categoria', 'estado', 'marca', 'unidadMedida', 'stocks', 
                    'precios' => fn($q) => $q->whereNull('fechaHasta')]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhereHas('marca', fn($mq) => $mq->where('nombre', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoriaProductoID', $request->input('categoria_id'));
        }

        if ($request->filled('estado_id')) {
            $query->where('estadoProductoID', $request->input('estado_id'));
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_habitual_id', $request->input('proveedor_id'));
        }

        if ($request->input('stock_bajo') === 'true') {
            $query->whereHas('stocks', function (Builder $q) {
                $q->whereRaw('cantidad_disponible <= stock_minimo');
            });
        }

        $sortColumn = $request->input('sort_column', 'nombre');
        $sortDirection = $request->input('sort_direction', 'asc');
        $productos = $query->orderBy($sortColumn, $sortDirection)
                           ->paginate(10)
                           ->withQueryString();

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'estados' => EstadoProducto::all(['id', 'nombre']),
            'proveedores' => Proveedor::where('activo', true)->get(['id', 'razon_social']),
            'filters' => $request->only(['search', 'categoria_id', 'estado_id', 'stock_bajo', 'proveedor_id']),
            'stats' => [
                'total' => Producto::count(),
                'activos' => Producto::where('estadoProductoID', 1)->count(), 
                'stockBajo' => Stock::whereRaw('cantidad_disponible <= stock_minimo')->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Productos/Create', [
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'estados' => EstadoProducto::all(['id', 'nombre']),
            
            // CORRECCIÓN: Mapeamos correctamente para la vista
            'tiposCliente' => TipoCliente::where('activo', true)
                ->get()
                ->map(fn($t) => ['id' => $t->tipoClienteID, 'nombre' => $t->nombreTipo]),
            
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => UnidadMedida::where('activo', true)->orderBy('nombre')->get(),
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
        ]);
    }

    public function store(StoreProductoRequest $request, RegistrarProductoService $service)
    {
        try {
            $producto = $service->handle($request->validated(), auth()->id());

            return redirect()->route('productos.show', $producto->id)
                             ->with('success', 'Producto registrado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al registrar producto: '.$e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: '.$e->getMessage()]);
        }
    }

    public function show(Producto $producto)
    {
        $producto->load([
            'categoria', 'estado', 'marca', 'unidadMedida',
            'precios.tipoCliente',
            'stocks.deposito',
            'proveedorHabitual'
        ]);

        $stockTotal = $producto->stocks->sum('cantidad_disponible');
        
        $movimientos = MovimientoStock::where('productoID', $producto->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        return Inertia::render('Productos/Show', [
            'producto' => $producto,
            'movimientos' => $movimientos,
            'stockTotal' => $stockTotal, 
        ]);
    }

    public function edit(Producto $producto)
    {
        $producto->load(['precios.tipoCliente']);

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'estados' => EstadoProducto::all(['id', 'nombre']),
            
            // CORRECCIÓN: Mismo mapeo que en create
            'tiposCliente' => TipoCliente::where('activo', true)
                ->get()
                ->map(fn($t) => ['id' => $t->tipoClienteID, 'nombre' => $t->nombreTipo]),
            
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => UnidadMedida::where('activo', true)->orderBy('nombre')->get(),
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
        ]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto, UpdateProductoService $service)
    {
        try {
            $service->handle($producto, $request->validated(), auth()->id());

            return redirect()->route('productos.show', $producto->id)
                             ->with('success', 'Producto modificado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al modificar producto: '.$e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: '.$e->getMessage()]);
        }
    }

    public function darDeBaja(DarDeBajaProductoRequest $request, Producto $producto)
    {
        try {
            $producto->darDeBaja($request->motivo, auth()->id());
            return redirect()->route('productos.index')->with('success', 'Producto dado de baja exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al dar de baja producto: '.$e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function stock(Request $request)
    {
        $query = Stock::query()->with(['producto.categoria', 'deposito']);

        if ($request->filled('deposito_id')) {
            $query->where('deposito_id', $request->input('deposito_id'));
        }
        if ($request->filled('categoria_id')) {
            $query->whereHas('producto', fn($q) => $q->where('categoriaProductoID', $request->input('categoria_id')));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->whereHas('producto', fn($q) => $q->where('nombre', 'like', "%$s%")->orWhere('codigo', 'like', "%$s%"));
        }
        if ($request->input('stock_bajo') === 'true') {
            $query->whereRaw('cantidad_disponible <= stock_minimo');
        }

        $stocks = $query->orderBy('productoID', 'asc')->paginate(15)->withQueryString();

        return Inertia::render('Productos/Stock', [
            'stocks' => $stocks, 
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'depositos' => \App\Models\Deposito::where('activo', true)->get(['deposito_id', 'nombre']),
            'filters' => $request->only(['search', 'categoria_id', 'stock_bajo', 'deposito_id']),
            'stats' => [
                'totalItems' => Stock::count(),
                'valorizado' => 0,
                'stockBajo' => Stock::whereRaw('cantidad_disponible <= stock_minimo')->count(),
            ],
        ]);
    }
}