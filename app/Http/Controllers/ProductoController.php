<?php

namespace App\Http\Controllers;

// Modelos
use App\Models\Auditoria;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\MovimientoStock;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Proveedor; // Importante para el filtro
use App\Models\TipoCliente;
use App\Models\Stock; // Importante para CU-29

// --- ARQUITECTURA LARMAN (BCE) ---
// 1. Boundaries (Validación)
use App\Http\Requests\Productos\StoreProductoRequest;
use App\Http\Requests\Productos\UpdateProductoRequest;
use App\Http\Requests\Productos\DarDeBajaProductoRequest;

// 2. Controls (Lógica de Negocio)
use App\Services\Productos\RegistrarProductoService;
use App\Services\Productos\UpdateProductoService;
// --- FIN ARQUITECTURA LARMAN ---

// Clases de Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder; // Importar Builder

class ProductoController extends Controller
{
    /**
     * CU-28: Consultar Productos (Catálogo)
     * Muestra una lista de productos con filtros.
     * CORREGIDO: Carga precios y proveedores para la vista mejorada.
     */
    public function index(Request $request)
    {
        // 1. Consulta Base (Catálogo)
        // Cargamos 'precios' (solo vigentes) para mostrar "Precio Minorista" en la tabla
        $query = Producto::query()
            ->with([
                'categoria', 
                'estado', 
                'stocks',
                'precios' => function ($q) {
                    $q->whereNull('fechaHasta');
                }
            ]);

        // 2. Filtros
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%')
                    ->orWhere('codigo', 'like', '%'.$searchTerm.'%')
                    ->orWhere('marca', 'like', '%'.$searchTerm.'%');
            });
        }

        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            $query->where('categoriaProductoID', $request->input('categoria_id'));
        }

        if ($request->has('estado_id') && $request->input('estado_id')) {
            $query->where('estadoProductoID', $request->input('estado_id'));
        }

        // [NUEVO] Filtro por Proveedor Habitual
        if ($request->has('proveedor_id') && $request->input('proveedor_id')) {
            $query->where('proveedor_habitual_id', $request->input('proveedor_id'));
        }

        // Filtro de Stock Bajo (Usando la relación con la tabla 'stocks')
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereHas('stocks', function (Builder $q) {
                $q->whereRaw('cantidad_disponible <= stock_minimo');
            });
        }

        // 3. Contadores (Stats)
        $totalProductos = Producto::count();
        $productosActivos = Producto::whereHas('estado', function ($q) {
            $q->where('nombre', 'Activo');
        })->count();
        
        $stockBajo = Producto::whereHas('stocks', function (Builder $q) {
                $q->whereRaw('cantidad_disponible <= stock_minimo');
            })->count();

        // 4. Ordenamiento
        $sortColumn = $request->input('sort_column', 'nombre');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortColumns = ['nombre', 'codigo', 'marca', 'created_at'];
        if (! in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'nombre';
        }

        $productos = $query->orderBy($sortColumn, $sortDirection)
                           ->paginate(10)
                           ->withQueryString();

        // 5. Datos para los Selects de la vista
        $categoriasFormateadas = CategoriaProducto::where('activo', true)
            ->get(['id', 'nombre'])
            ->map(fn($cat) => ['id' => $cat->id, 'nombre' => $cat->nombre])
            ->values()->all();

        $estadosFormateados = EstadoProducto::all(['id', 'nombre'])
            ->map(fn($est) => ['id' => $est->id, 'nombre' => $est->nombre])
            ->values()->all();
        
        // [NUEVO] Proveedores para el filtro
        $proveedores = Proveedor::where('activo', true)->get(['id', 'razon_social']);

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'categorias' => $categoriasFormateadas,
            'estados' => $estadosFormateados,
            'proveedores' => $proveedores, // Enviamos los proveedores
            'filters' => $request->only(['search', 'categoria_id', 'estado_id', 'stock_bajo', 'sort_column', 'sort_direction', 'proveedor_id']),
            'stats' => [
                'total' => $totalProductos,
                'activos' => $productosActivos,
                'stockBajo' => $stockBajo,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo producto
     */
    public function create()
    {
        $categorias = CategoriaProducto::where('activo', true)->get(['id', 'nombre']);
        $estados = EstadoProducto::all(['id', 'nombre']);
        $tiposCliente = TipoCliente::all(['tipoClienteID as id', 'nombreTipo as nombre']);
        
        $proveedores = Proveedor::where('activo', true)->get(['id', 'razon_social']); 

        return Inertia::render('Productos/Create', [
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * CU-25: Registrar Producto
     */
    public function store(StoreProductoRequest $request, RegistrarProductoService $service)
    {
        try {
            $producto = $service->handle($request->validated(), auth()->id());

            return redirect()->route('productos.show', $producto->id)
                             ->with('success', 'Producto registrado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al registrar producto: '.$e->getMessage(), ['exception' => $e]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar el producto: '.$e->getMessage()]);
        }
    }

    /**
     * CU-28: Consultar Producto (detalle completo)
     */
    public function show(Producto $producto)
    {
        // Cargamos relaciones y stock
        $producto->load([
            'categoria', 
            'estado', 
            'precios.tipoCliente',
            'stocks.deposito',
            'proveedorHabitual' // Cargar proveedor para mostrarlo
        ]);

        // Calculamos stock total
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

    /**
     * Muestra el formulario para editar un producto
     */
    public function edit(Producto $producto)
    {
        $producto->load(['precios.tipoCliente']);

        $categorias = CategoriaProducto::where('activo', true)->get(['id', 'nombre']);
        $estados = EstadoProducto::all(['id', 'nombre']);
        $tiposCliente = TipoCliente::all(['tipoClienteID as id', 'nombreTipo as nombre']);
        
        $proveedores = Proveedor::where('activo', true)->get(['id', 'razon_social']);

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * CU-26: Modificar Producto
     */
    public function update(UpdateProductoRequest $request, Producto $producto, UpdateProductoService $service)
    {
        try {
            $service->handle($producto, $request->validated(), auth()->id());

            return redirect()->route('productos.show', $producto->id)
                             ->with('success', 'Producto modificado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al modificar producto: '.$e->getMessage(), ['exception' => $e]);
            return back()->withInput()->withErrors(['error' => 'Error al modificar el producto: '.$e->getMessage()]);
        }
    }

    /**
     * CU-27: Dar de Baja un Producto
     */
    public function darDeBaja(DarDeBajaProductoRequest $request, Producto $producto)
    {
        try {
            $producto->darDeBaja($request->motivo, auth()->id());

            return redirect()->route('productos.index')
                             ->with('success', 'Producto dado de baja exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al dar de baja producto: '.$e->getMessage(), ['exception' => $e]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * CU-29: Consultar Stock (Inventario)
     */
    /**
     * CU-29: Consultar Stock (Inventario)
     */
    public function stock(Request $request)
    {
        // Consulta principal desde la tabla STOCK
        $query = Stock::query()->with([
            'producto.categoria',
            'deposito'
        ]);

        // 1. Filtro por Depósito (Requerido por CU-29 Paso 2)
        if ($request->has('deposito_id') && $request->input('deposito_id')) {
            $query->where('deposito_id', $request->input('deposito_id'));
        }

        // 2. Filtro por Categoría
        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            $query->whereHas('producto', function (Builder $q) use ($request) {
                $q->where('categoriaProductoID', $request->input('categoria_id'));
            });
        }
        
        // 3. Búsqueda General (Código o Nombre)
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('producto', function (Builder $q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%')
                  ->orWhere('codigo', 'like', '%'.$searchTerm.'%');
            });
        }
        
        // 4. Filtro Stock Bajo
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereRaw('cantidad_disponible <= stock_minimo');
        }

        // Ordenamiento por defecto
        $stocks = $query->orderBy('productoID', 'asc')
                        ->paginate(15) // Paginación
                        ->withQueryString();

        return Inertia::render('Productos/Stock', [
            'stocks' => $stocks, 
            // Cargamos los catálogos para los filtros
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'depositos' => \App\Models\Deposito::where('activo', true)->get(['deposito_id', 'nombre']), // <--- Agregado
            'filters' => $request->only(['search', 'categoria_id', 'stock_bajo', 'deposito_id']),
            'stats' => [
                'totalItems' => Stock::count(),
                'valorizado' => 0, // Podrías calcularlo si tuvieras costo
                'stockBajo' => Stock::whereRaw('cantidad_disponible <= stock_minimo')->count(),
            ],
        ]);
    }
}