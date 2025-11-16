<?php

namespace App\Http\Controllers;

// Modelos
use App\Models\Auditoria;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\MovimientoStock;
use App\Models\PrecioProducto;
use App\Models\Producto;
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
     * CU-28: Consultar Productos
     * Muestra una lista de productos con filtros
     */
    public function index(Request $request)
    {
        // Esta lógica de consulta (lectura) está bien en el controlador
        $query = Producto::query()
            ->with(['categoria', 'estado']);

        // Filtros de búsqueda
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

        // CORREGIDO: Usar la columna 'stockActual' (Single Depot)
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereRaw('stockActual <= stockMinimo');
        }

        // Contadores para estadísticas
        $totalProductos = Producto::count();
        $productosActivos = Producto::whereHas('estado', function ($q) {
            $q->where('nombre', 'Activo');
        })->count();
        // CORREGIDO: Usar la columna 'stockActual'
        $stockBajo = Producto::whereRaw('stockActual <= stockMinimo')->count();

        // Ordenamiento
        $sortColumn = $request->input('sort_column', 'nombre');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortColumns = ['nombre', 'codigo', 'marca', 'stockActual', 'created_at'];
        if (! in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'nombre';
        }

        $productos = $query->orderBy($sortColumn, $sortDirection)->paginate(10)->withQueryString();

        // Formatear datos para Vue
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
        // Esta lógica está bien, solo prepara datos para la vista
        $categorias = CategoriaProducto::where('activo', true)->get(['id', 'nombre']);
        $estados = EstadoProducto::all(['id', 'nombre']);
        $tiposCliente = TipoCliente::all(['tipoClienteID as id', 'nombreTipo as nombre']);

        return Inertia::render('Productos/Create', [
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
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
    public function show(Producto $producto)
    {
        // Lógica de lectura, está bien aquí
        $producto->load(['categoria', 'estado', 'precios.tipoCliente']);

        // CORREGIDO: Usar la FK correcta 'productoID'
        $movimientos = MovimientoStock::where('productoID', $producto->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // (La auditoría de consulta la quitamos, es mucho ruido. La auditoría de C/U/D es la importante)
        
        return Inertia::render('Productos/Show', [
            'producto' => $producto,
            'movimientos' => $movimientos,
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

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
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
    public function stock(Request $request)
    {
        // Lógica de consulta está bien aquí
        $query = Producto::query()->with(['categoria']);

        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            $query->where('categoriaProductoID', $request->input('categoria_id'));
        }
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%')
                    ->orWhere('codigo', 'like', '%'.$searchTerm.'%');
            });
        }
        
        // CORREGIDO: Usar la columna 'stockActual'
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereRaw('stockActual <= stockMinimo');
        }

        $productos = $query->orderBy('nombre')->paginate(10)->withQueryString();
        $stockBajo = Producto::whereRaw('stockActual <= stockMinimo')->count();

        return Inertia::render('Productos/Stock', [
            'productos' => $productos,
            'categorias' => CategoriaProducto::where('activo', true)->get(['id', 'nombre']),
            'filters' => $request->only(['search', 'categoria_id', 'stock_bajo']),
            'stats' => [
                'stockBajo' => $stockBajo,
                // ...
            ],
        ]);
    }
}
