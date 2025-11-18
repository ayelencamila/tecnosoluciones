<?php

namespace App\Http\Controllers;

// Modelos
use App\Models\Auditoria;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\MovimientoStock;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\TipoCliente;

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
     * Muestra una lista de productos con filtros
     * ¡CORREGIDO para consultar el stock desde la tabla 'stocks'!
     */
    public function index(Request $request)
    {
        // La consulta principal al catálogo (Producto) es correcta
        $query = Producto::query()
            ->with(['categoria', 'estado', 'stocks']); // Cargamos la relación de stock

        // --- Filtros (Tus filtros de search, categoria y estado están perfectos) ---
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

        // --- CORRECCIÓN DEL FILTRO DE STOCK BAJO ---
        // Ahora filtramos usando la relación 'stocks'
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            // whereHas filtra Productos que tienen al menos un registro en 'stocks'
            // que cumple la condición
            $query->whereHas('stocks', function (Builder $q) {
                $q->whereRaw('cantidad_disponible <= stock_minimo');
            });
        }

        // --- CORRECIÓN DE CONTADORES (STATS) ---
        $totalProductos = Producto::count();
        $productosActivos = Producto::whereHas('estado', function ($q) {
            $q->where('nombre', 'Activo');
        })->count();
        
        // El conteo de stock bajo ahora también usa whereHas
        $stockBajo = Producto::whereHas('stocks', function (Builder $q) {
                $q->whereRaw('cantidad_disponible <= stock_minimo');
            })->count();


        // --- CORRECIÓN DE ORDENAMIENTO (SORTING) ---
        $sortColumn = $request->input('sort_column', 'nombre');
        $sortDirection = $request->input('sort_direction', 'asc');

        // Quitamos 'stockActual' de las columnas permitidas para ordenar en esta vista
        // (Ordenar por stock es complejo aquí, lo dejamos para la vista de Stock)
        $allowedSortColumns = ['nombre', 'codigo', 'marca', 'created_at'];
        if (! in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'nombre';
        }

        $productos = $query->orderBy($sortColumn, $sortDirection)->paginate(10)->withQueryString();

        // (El resto de tu lógica para formatear categorías y estados está perfecta)
        $categoriasFormateadas = CategoriaProducto::where('activo', true)
            ->get(['id', 'nombre'])
            ->map(fn($cat) => ['id' => $cat->id, 'nombre' => $cat->nombre])
            ->values()->all();

        $estadosFormateados = EstadoProducto::all(['id', 'nombre'])
            ->map(fn($est) => ['id' => $est->id, 'nombre' => $est->nombre])
            ->values()->all();

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'categorias' => $categoriasFormateadas,
            'estados' => $estadosFormateados,
            'filters' => $request->only(['search', 'categoria_id', 'estado_id', 'stock_bajo', 'sort_column', 'sort_direction']),
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
        
        // Ahora sí funcionará porque importamos App\Models\Proveedor
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
     * ¡REFACTORIZADO!
     */
    public function store(StoreProductoRequest $request, RegistrarProductoService $service)
    {
        // 1. Validado por StoreProductoRequest
        // 2. Lógica de Negocio delegada al Servicio
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
    /**
     * CU-28: Consultar Producto (detalle completo)
     * ¡CORREGIDO para cargar el stock total!
     */
    public function show(Producto $producto)
    {
        // 1. Cargamos todas las relaciones necesarias
        $producto->load([
            'categoria', 
            'estado', 
            'precios.tipoCliente',
            'stocks.deposito' // <-- Cargamos el/los stocks y el depósito
        ]);

        // 2. Calculamos el stock total sumando todos sus depósitos
        // (Aunque ahora es uno solo, esto funcionará para N depósitos)
        $stockTotal = $producto->stocks->sum('cantidad_disponible');
        
        // 3. Buscamos movimientos (Tu lógica estaba correcta)
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
        
        // NUEVO: Agregamos esta línea
        $proveedores = \App\Models\Proveedor::where('activo', true)->get(['id', 'razon_social']);

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
            'proveedores' => $proveedores, // <-- Y la pasamos a la vista
        ]);
    }

    /**
     * CU-26: Modificar Producto
     * ¡REFACTORIZADO!
     */
    public function update(UpdateProductoRequest $request, Producto $producto, UpdateProductoService $service)
    {
        // 1. Validado por UpdateProductoRequest
        // 2. Lógica de Precios/Auditoría delegada al Servicio
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
     * ¡REFACTORIZADO!
     */
    public function darDeBaja(DarDeBajaProductoRequest $request, Producto $producto)
    {
        // 1. Validado por DarDeBajaProductoRequest (pide 'motivo')
        // 2. Lógica de negocio delegada al Modelo (Larman's Expert)
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
     * CU-29: Consultar Stock
     * Vista específica para consultar stock
     */
    /**
     * CU-29: Consultar Stock
     * Vista específica para consultar stock
     */
    public function stock(Request $request)
    {
        // 1. La consulta AHORA EMPIEZA DESDE Stock
        // Cargamos las relaciones que necesitamos mostrar
        $query = Stock::query()->with([
            'producto.categoria', // Carga el producto y su categoría anidada
            'deposito'
        ]);

        // 2. Filtros (se aplican sobre las relaciones)
        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            // whereHas filtra el Stock basado en una condición de su 'producto'
            $query->whereHas('producto', function (Builder $q) use ($request) {
                $q->where('categoriaProductoID', $request->input('categoria_id'));
            });
        }
        
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('producto', function (Builder $q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%')
                  ->orWhere('codigo', 'like', '%'.$searchTerm.'%');
            });
        }
        
        // 3. Filtro de stock_bajo (AHORA USA LAS COLUMNAS DE LA TABLA 'stock')
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereRaw('cantidad_disponible <= stock_minimo');
        }

        // 4. Conteo de stock bajo (AHORA USA LA TABLA 'stock')
        $stockBajoCount = Stock::whereRaw('cantidad_disponible <= stock_minimo')->count();

        // 5. Paginación
        // (Nota: No podemos ordenar por 'producto.nombre' directamente en paginate,
        // pero lo haremos en el siguiente paso si es necesario. Por ahora, ordenamos por ID de stock).
        $stocks = $query->orderBy('stock_id', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Productos/Stock', [
            // Pasamos los 'stocks' (que incluyen los 'productos' anidados)
            'stocks' => $stocks, 
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'filters' => $request->only(['search', 'categoria_id', 'stock_bajo']),
            'stats' => [
                'stockBajo' => $stockBajoCount,
            ],
        ]);
    }
}
