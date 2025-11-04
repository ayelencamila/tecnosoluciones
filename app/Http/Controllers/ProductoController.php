<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\TipoCliente;
use App\Models\PrecioProducto;
use App\Models\MovimientoStock;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductoController extends Controller
{
    /**
     * CU-28: Consultar Productos
     * Muestra una lista de productos con filtros
     */
    public function index(Request $request)
    {
        $query = Producto::query()
            ->with(['categoria', 'estado']);

        // Filtros de búsqueda
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('codigo', 'like', '%' . $searchTerm . '%')
                    ->orWhere('marca', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            $query->where('categoriaProductoID', $request->input('categoria_id'));
        }

        if ($request->has('estado_id') && $request->input('estado_id')) {
            $query->where('estadoProductoID', $request->input('estado_id'));
        }

        // Filtro de stock bajo
        if ($request->has('stock_bajo') && $request->input('stock_bajo') === 'true') {
            $query->whereRaw('stockActual <= stockMinimo');
        }

        // Contadores para estadísticas
        $totalProductos = Producto::count();
        $productosActivos = Producto::whereHas('estado', function ($q) {
            $q->where('nombre', 'Activo');
        })->count();
        $stockBajo = Producto::whereRaw('stockActual <= stockMinimo')->count();

        // Ordenamiento
        $sortColumn = $request->input('sort_column', 'nombre');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        $allowedSortColumns = ['nombre', 'codigo', 'marca', 'stockActual', 'created_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'nombre';
        }

        $productos = $query->orderBy($sortColumn, $sortDirection)->paginate(10);

        // Formatear datos para Vue
        $categoriasFormateadas = CategoriaProducto::where('activo', true)
            ->get(['id', 'nombre'])
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre
                ];
            })->values()->all();

        $estadosFormateados = EstadoProducto::all(['id', 'nombre'])
            ->map(function ($est) {
                return [
                    'id' => $est->id,
                    'nombre' => $est->nombre
                ];
            })->values()->all();

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'categorias' => $categoriasFormateadas,
            'estados' => $estadosFormateados,
            'filters' => array_merge(
                [
                    'search' => '',
                    'categoria_id' => '',
                    'estado_id' => '',
                    'stock_bajo' => false,
                    'sort_column' => $sortColumn,
                    'sort_direction' => $sortDirection,
                ],
                $request->only(['search', 'categoria_id', 'estado_id', 'stock_bajo', 'sort_column', 'sort_direction'])
            ),
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

        return Inertia::render('Productos/Create', [
            'categorias' => $categorias,
            'estados' => $estados,
            'tiposCliente' => $tiposCliente,
        ]);
    }

    /**
     * CU-25: Registrar Producto
     * Almacena un nuevo producto con sus precios
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            'stockActual' => 'nullable|integer|min:0',
            'stockMinimo' => 'nullable|integer|min:0',
            'precios' => 'required|array|min:1',
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID',
            'precios.*.precio' => 'required|numeric|min:0',
        ], [
            'codigo.unique' => 'El código/SKU ya existe en el sistema',
            'precios.required' => 'Debe ingresar al menos un precio (minorista o mayorista)',
        ]);

        DB::beginTransaction();
        try {
            // Crear producto
            $producto = Producto::create([
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'marca' => $validated['marca'] ?? null,
                'unidadMedida' => $validated['unidadMedida'],
                'categoriaProductoID' => $validated['categoriaProductoID'],
                'estadoProductoID' => $validated['estadoProductoID'],
                'stockActual' => $validated['stockActual'] ?? 0,
                'stockMinimo' => $validated['stockMinimo'] ?? 0,
            ]);

            // Crear precios
            $fechaActual = now()->toDateString();
            foreach ($validated['precios'] as $precioData) {
                PrecioProducto::create([
                    'productoID' => $producto->id,
                    'tipoClienteID' => $precioData['tipoClienteID'],
                    'precio' => $precioData['precio'],
                    'fechaDesde' => $fechaActual,
                    'fechaHasta' => null, // Vigente
                ]);
            }

            // Registrar en auditoría
            Auditoria::create([
                'tablaAfectada' => 'productos',
                'registroID' => $producto->id,
                'accion' => 'CREAR',
                'datosNuevos' => json_encode($producto->toArray()),
                'usuarioID' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('productos.index')
                ->with('success', 'Producto registrado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar producto: ' . $e->getMessage());
            
            return back()->withErrors([
                'error' => 'Error al registrar el producto. Intente nuevamente.'
            ])->withInput();
        }
    }

    /**
     * CU-28: Consultar Producto (detalle completo)
     * Muestra la ficha completa de un producto
     */
    public function show(Producto $producto)
    {
        $producto->load(['categoria', 'estado', 'precios.tipoCliente']);

        // Obtener historial de movimientos de stock
        $movimientos = MovimientoStock::where('productoID', $producto->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Registrar consulta en auditoría
        Auditoria::create([
            'tablaAfectada' => 'productos',
            'registroID' => $producto->id,
            'accion' => 'CONSULTAR',
            'usuarioID' => auth()->id(),
        ]);

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
     * Actualiza un producto existente
     */
    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($producto->id)],
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            'stockActual' => 'nullable|integer|min:0',
            'stockMinimo' => 'nullable|integer|min:0',
            'motivo' => 'required|string|min:5|max:255',
            'precios' => 'required|array|min:1',
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID',
            'precios.*.precio' => 'required|numeric|min:0',
        ], [
            'motivo.required' => 'Debe ingresar un motivo para la modificación',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres',
        ]);

        DB::beginTransaction();
        try {
            $datosAnteriores = $producto->toArray();

            // Actualizar producto
            $producto->update([
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'marca' => $validated['marca'] ?? null,
                'unidadMedida' => $validated['unidadMedida'],
                'categoriaProductoID' => $validated['categoriaProductoID'],
                'estadoProductoID' => $validated['estadoProductoID'],
                'stockActual' => $validated['stockActual'] ?? 0,
                'stockMinimo' => $validated['stockMinimo'] ?? 0,
            ]);

            // Actualizar precios: cerrar vigentes y crear nuevos si cambiaron
            $fechaActual = now()->toDateString();
            foreach ($validated['precios'] as $precioData) {
                $precioVigente = $producto->precioVigente($precioData['tipoClienteID']);
                
                if ($precioVigente && $precioVigente->precio != $precioData['precio']) {
                    // Cerrar precio anterior
                    $precioVigente->update(['fechaHasta' => $fechaActual]);
                    
                    // Crear nuevo precio
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                    ]);
                } elseif (!$precioVigente) {
                    // Crear precio si no existía
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                    ]);
                }
            }

            // Registrar en auditoría con motivo
            Auditoria::create([
                'tablaAfectada' => 'productos',
                'registroID' => $producto->id,
                'accion' => 'MODIFICAR',
                'datosAnteriores' => json_encode($datosAnteriores),
                'datosNuevos' => json_encode($producto->fresh()->toArray()),
                'motivo' => $validated['motivo'],
                'usuarioID' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('productos.show', $producto)
                ->with('success', 'Producto modificado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al modificar producto: ' . $e->getMessage());
            
            return back()->withErrors([
                'error' => 'Error al modificar el producto. Intente nuevamente.'
            ])->withInput();
        }
    }

    /**
     * CU-27: Dar de Baja un Producto
     * Cambia el estado del producto a "Inactivo"
     */
    public function destroy(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'motivo' => 'required|string|min:5|max:255',
        ], [
            'motivo.required' => 'Debe ingresar un motivo para dar de baja el producto',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres',
        ]);

        DB::beginTransaction();
        try {
            // Verificar que no esté ya inactivo
            $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')->first();
            
            if ($producto->estadoProductoID == $estadoInactivo->id) {
                return back()->withErrors([
                    'error' => 'El producto ya se encuentra inactivo'
                ]);
            }

            $datosAnteriores = $producto->toArray();

            // Cambiar estado a Inactivo
            $producto->update([
                'estadoProductoID' => $estadoInactivo->id
            ]);

            // Registrar en auditoría con motivo
            Auditoria::create([
                'tablaAfectada' => 'productos',
                'registroID' => $producto->id,
                'accion' => 'ELIMINAR',
                'datosAnteriores' => json_encode($datosAnteriores),
                'datosNuevos' => json_encode($producto->fresh()->toArray()),
                'motivo' => $validated['motivo'],
                'usuarioID' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('productos.index')
                ->with('success', 'Producto dado de baja exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al dar de baja producto: ' . $e->getMessage());
            
            return back()->withErrors([
                'error' => 'Error al dar de baja el producto. Intente nuevamente.'
            ]);
        }
    }

    /**
     * CU-29: Consultar Stock
     * Vista específica para consultar stock con filtros
     */
    public function stock(Request $request)
    {
        $query = Producto::query()
            ->with(['categoria', 'estado']);

        // Filtros
        if ($request->has('categoria_id') && $request->input('categoria_id')) {
            $query->where('categoriaProductoID', $request->input('categoria_id'));
        }

        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('codigo', 'like', '%' . $searchTerm . '%');
            });
        }

        // Contadores para estadísticas
        $totalProductos = Producto::count();
        $productosActivos = Producto::whereHas('estado', function ($q) {
            $q->where('nombre', 'Activo');
        })->count();
        $stockBajo = Producto::whereRaw('stockActual <= stockMinimo')->count();

        $productos = $query->orderBy('nombre')->paginate(10);

        // Formatear datos para Vue
        $categoriasFormateadas = CategoriaProducto::where('activo', true)
            ->get(['id', 'nombre'])
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre
                ];
            })->values()->all();

        $estadosFormateados = EstadoProducto::all(['id', 'nombre'])
            ->map(function ($est) {
                return [
                    'id' => $est->id,
                    'nombre' => $est->nombre
                ];
            })->values()->all();

        // Registrar consulta en auditoría
        Auditoria::create([
            'tablaAfectada' => 'productos',
            'registroID' => null,
            'accion' => 'CONSULTAR_STOCK',
            'usuarioID' => auth()->id(),
        ]);

        return Inertia::render('Productos/Stock', [
            'productos' => $productos,
            'categorias' => $categoriasFormateadas,
            'estados' => $estadosFormateados,
            'filters' => array_merge(
                [
                    'search' => '',
                    'categoria_id' => '',
                    'stock_bajo' => false,
                ],
                $request->only(['search', 'categoria_id', 'stock_bajo'])
            ),
            'stats' => [
                'total' => $totalProductos,
                'activos' => $productosActivos,
                'stockBajo' => $stockBajo,
            ],
        ]);
    }
}
