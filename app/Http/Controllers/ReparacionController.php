<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

// Modelos
use App\Models\Reparacion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\EstadoReparacion;

// Requests (Validaciones)
use App\Http\Requests\Reparaciones\StoreReparacionRequest;
use App\Http\Requests\Reparaciones\AnularReparacionRequest;

// Servicios (Lógica de Negocio)
use App\Services\Reparaciones\RegistrarReparacionService;
use App\Services\Reparaciones\ActualizarReparacionService;
use App\Services\Reparaciones\AnularReparacionService;

// Excepciones
use App\Exceptions\Ventas\SinStockException;

class ReparacionController extends Controller
{
    /**
     * CU-13 (Parte 1): Listar Reparaciones
     * Muestra el listado con filtros y paginación.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'estado_id']);

        // 1. CARGAMOS LAS NUEVAS RELACIONES (Marca, Modelo)
        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'marca', 'modelo'])
            ->latest();

        // Filtro de Búsqueda General (Actualizado para buscar por Marca/Modelo)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_reparacion', 'like', "%{$search}%")
                  ->orWhere('numero_serie_imei', 'like', "%{$search}%")
                  // Búsqueda inteligente en las tablas relacionadas
                  ->orWhereHas('marca', fn($q) => $q->where('nombre', 'like', "%{$search}%"))
                  ->orWhereHas('modelo', fn($q) => $q->where('nombre', 'like', "%{$search}%"))
                  ->orWhereHas('cliente', function($c) use ($search) {
                      $c->where('apellido', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por Estado
        if ($request->filled('estado_id')) {
            $query->where('estado_reparacion_id', $request->estado_id);
        }

        // Transformación de datos para la vista
        $reparaciones = $query->paginate(10)
            ->withQueryString()
            ->through(fn ($r) => [
                'reparacionID' => $r->reparacionID,
                'codigo' => $r->codigo_reparacion,
                'fecha_ingreso' => $r->fecha_ingreso->format('d/m/Y H:i'),
                'fecha_promesa' => $r->fecha_promesa ? $r->fecha_promesa->format('d/m/Y') : null,
                'cliente' => [
                    'nombre_completo' => "{$r->cliente->apellido}, {$r->cliente->nombre}",
                    'telefono' => $r->cliente->whatsapp ?? $r->cliente->telefono ?? '-',
                ],
                // CORRECCIÓN CLAVE: Concatenamos Marca y Modelo desde las relaciones
                'equipo' => ($r->marca->nombre ?? 'Sin Marca') . ' ' . ($r->modelo->nombre ?? ''),
                'falla' => \Illuminate\Support\Str::limit($r->falla_declarada, 30),
                'estado' => [
                    'nombre' => $r->estado->nombreEstado,
                    'id' => $r->estado->estadoReparacionID
                ],
                'tecnico' => $r->tecnico ? $r->tecnico->name : 'Sin asignar',
            ]);

        return Inertia::render('Reparaciones/Index', [
            'reparaciones' => $reparaciones,
            'estados' => EstadoReparacion::all(),
            'filters' => $filters,
        ]);
    }

    /**
     * CU-11 (Parte 1): Formulario de Registro
     */
    public function create(): Response
    {
        return Inertia::render('Reparaciones/Create', [
            'clientes' => Cliente::select('clienteID', 'nombre', 'apellido', 'dni')->orderBy('apellido')->get(),
            'productos' => Producto::where('estadoProductoID', 1)->get(),
            
            // Se Envia todas las marcas activas
            'marcas' => \App\Models\Marca::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * CU-11 (Parte 2): Procesar el Registro
     */
    public function store(StoreReparacionRequest $request, RegistrarReparacionService $service): RedirectResponse
    {
        try {
            // Delegamos toda la lógica compleja al Servicio
            $reparacion = $service->handle(
                $request->validated(), 
                $request->user()->id
            );

            return redirect()->route('reparaciones.index')
                ->with('success', "Reparación registrada con éxito. Código: {$reparacion->codigo_reparacion}");

        } catch (SinStockException $e) {
            // Error de dominio específico (Stock)
            return back()->withErrors(['items' => $e->getMessage()])->withInput();

        } catch (\Exception $e) {
            // Error inesperado
            Log::error("Error al registrar reparación: " . $e->getMessage());
            return back()
                ->withErrors(['error' => 'Ocurrió un error inesperado. Por favor intente nuevamente.'])
                ->withInput();
        }
    }

    /**
     * CU-13 (Parte 2): Ver Detalle de Reparación
     */
    public function show($id): Response
    {
        // Cargamos todas las relaciones necesarias para la ficha técnica
        $reparacion = Reparacion::with([
            'cliente', 
            'tecnico', 
            'estado', 
            'imagenes', 
            'repuestos.producto' // Para ver el detalle de ítems usados
        ])->findOrFail($id);

        return Inertia::render('Reparaciones/Show', [
            'reparacion' => $reparacion
        ]);
    }

    /**
     * CU-12 (Parte 1): Formulario de Edición / Diagnóstico
     */
    public function edit($id): Response
    {
        $reparacion = Reparacion::with(['cliente', 'repuestos.producto'])->findOrFail($id);

        // --- LÓGICA DE FILTRADO ROBUSTA ---
        // 1. Intentamos buscar categorías que suenen a "Repuesto" o "Insumo"
        $categoriasRepuestos = \App\Models\CategoriaProducto::where('nombre', 'like', '%Repuesto%')
            ->orWhere('nombre', 'like', '%Insumo%')
            ->pluck('id');

        $queryProductos = Producto::where('estadoProductoID', 1); // Solo activos

        if ($categoriasRepuestos->isNotEmpty()) {
            // OPCIÓN A: Si encontramos categorías de repuestos, filtramos por ellas (Lista blanca)
            $queryProductos->whereIn('categoriaProductoID', $categoriasRepuestos);
        } else {
            // OPCIÓN B (Respaldo): Si no existen, excluimos lo que seguro NO es repuesto (Equipos y Servicios)
            // Esto evita que aparezcan celulares nuevos en la lista si la categoría "Repuestos" no existe.
            $categoriasExcluidas = \App\Models\CategoriaProducto::where('nombre', 'like', '%Equipo%')
                ->orWhere('nombre', 'like', '%Servicio%')
                ->pluck('id');
                
            if ($categoriasExcluidas->isNotEmpty()) {
                $queryProductos->whereNotIn('categoriaProductoID', $categoriasExcluidas);
            }
        }

        return Inertia::render('Reparaciones/Edit', [
            'reparacion' => $reparacion,
            'estados' => EstadoReparacion::all(),
            // Mapeamos para la vista
            'productos' => $queryProductos->orderBy('nombre')->get()->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'stock_total' => $p->stock_total 
            ]),
        ]);
    }

    /**
     * CU-12 (Parte 2): Procesar Actualización
     */
    public function update(Request $request, $id, ActualizarReparacionService $service): RedirectResponse
    {
        // Validación simple (puedes extraerla a un FormRequest si crece)
        $validated = $request->validate([
            'estado_reparacion_id' => 'required|exists:estados_reparacion,estadoReparacionID',
            'diagnostico_tecnico' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:1000',
            'tecnico_id' => 'nullable|exists:users,id',
            // Validación de repuestos nuevos
            'repuestos' => 'nullable|array',
            'repuestos.*.producto_id' => 'required|exists:productos,id',
            'repuestos.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            $reparacion = Reparacion::findOrFail($id);
            
            // Delegamos actualización y gestión de stock al servicio
            $service->handle($reparacion, $validated, $request->user()->id);

            return redirect()->route('reparaciones.show', $id)
                ->with('success', 'Reparación actualizada correctamente.');

        } catch (SinStockException $e) {
            return back()->withErrors(['repuestos' => $e->getMessage()])->withInput();

        } catch (\Exception $e) {
            Log::error("Error al actualizar reparación {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar la reparación.'])->withInput();
        }
    }

    /**
     * CU-Anular: Anular Reparación y Revertir Stock
     */
    public function destroy(AnularReparacionRequest $request, $id, AnularReparacionService $service): RedirectResponse
    {
        try {
            $reparacion = Reparacion::with('repuestos.producto')->findOrFail($id);
            
            // El servicio se encarga de cambiar estado y devolver stock
            $service->handle($reparacion, $request->motivo, $request->user()->id);

            return redirect()->route('reparaciones.index')
                ->with('success', 'La reparación ha sido anulada y el stock de repuestos (si los hubo) ha sido revertido.');

        } catch (\Exception $e) {
            Log::error("Error al anular reparación {$id}: " . $e->getMessage());
            
            // Mensaje amigable si es una regla de negocio (ej: ya entregada)
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    /**
     * API para buscador asíncrono (Select de Reparaciones/Ventas)
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $clientes = Cliente::where('nombre', 'like', "%{$query}%")
            ->orWhere('apellido', 'like', "%{$query}%")
            ->orWhere('dni', 'like', "%{$query}%")
            ->limit(10) // Límite para rendimiento
            ->get(['clienteID', 'nombre', 'apellido', 'dni']); // Solo datos necesarios

        return response()->json($clientes);
    }
}