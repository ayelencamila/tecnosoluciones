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
use App\Models\Marca; 

// Requests
use App\Http\Requests\Reparaciones\StoreReparacionRequest;
use App\Http\Requests\Reparaciones\AnularReparacionRequest;

// Servicios
use App\Services\Reparaciones\RegistrarReparacionService;
use App\Services\Reparaciones\ActualizarReparacionService;
use App\Services\Reparaciones\AnularReparacionService;
use App\Services\Comprobantes\ComprobanteService;

// Excepciones
use App\Exceptions\Ventas\SinStockException;

class ReparacionController extends Controller
{
    /**
     * CU-13 (Parte 1): Listar Reparaciones
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'estado_id']);
        
        // 1. Cargamos 'modelo.marca' para acceder al nombre de la marca (3FN)
        $query = Reparacion::with(['cliente', 'tecnico', 'estado', 'modelo.marca'])
            ->latest();

        // Filtro de Búsqueda General (delegado al modelo - Alta Cohesión)
        $query->search($request->search);

        // Filtro por Estado
        if ($request->filled('estado_id')) {
            $query->where('estado_reparacion_id', $request->estado_id);
        }

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
                // Mapeo correcto: Marca a través del Modelo
                'equipo' => ($r->modelo->marca->nombre ?? 'N/A') . ' ' . ($r->modelo->nombre ?? ''),
                
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
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
            'tecnicos' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * CU-11 (Parte 2): Procesar el Registro
     */
    public function store(StoreReparacionRequest $request, RegistrarReparacionService $service): RedirectResponse
    {
        try {
            // El servicio se encarga de todo (Transacciones, Stock, Imágenes)
            $reparacion = $service->handle(
                $request->validated(), 
                $request->user()->id
            );

            // CU-11 Paso 11: Redirigir al detalle para presentar el comprobante generado
            return redirect()->route('reparaciones.show', $reparacion->reparacionID)
                ->with('success', "Reparación registrada con éxito. Código: {$reparacion->codigo_reparacion}")
                ->with('mostrar_comprobante', true); // Flag para mostrar modal/alerta de impresión

        } catch (SinStockException $e) {
            // Error de negocio: Falta stock de repuestos
            return back()->withErrors(['items' => $e->getMessage()])->withInput();

        } catch (\Exception $e) {
            // Error técnico inesperado
            Log::error("Error al registrar reparación: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Ocurrió un error al procesar la solicitud. Por favor intente nuevamente.'])
                ->withInput();
        }
    }

    /**
     * CU-13 (Parte 2): Ver Detalle de Reparación
     */
    public function show($id): Response
    {
        // Cargamos relaciones profundas para la ficha técnica
        $reparacion = Reparacion::with([
            'cliente', 
            'tecnico', 
            'estado', 
            'imagenes', 
            'repuestos.producto',
            'modelo.marca' 
        ])->findOrFail($id);

        return Inertia::render('Reparaciones/Show', [
            'reparacion' => $reparacion
        ]);
    }

    /**
     * Imprimir Comprobante de Ingreso de Reparación
     * CU-11 Paso 11: "Confirma el registro exitoso de la reparación y presenta el comprobante generado"
     * 
     * @param int $id ID de la reparación
     * @param ComprobanteService $service Servicio para preparar datos
     * @return \Illuminate\View\View Vista del comprobante lista para imprimir
     */
    public function imprimirComprobanteIngreso($id, ComprobanteService $service)
    {
        $reparacion = Reparacion::with([
            'cliente', 
            'tecnico', 
            'estado', 
            'modelo.marca'
        ])->findOrFail($id);

        // Preparar datos siguiendo lineamientos de Kendall
        $datos = $service->prepararDatosComprobanteIngresoReparacion($reparacion);

        // Retornar vista Blade optimizada para impresión con window.print()
        return view('comprobantes.comprobante-ingreso-reparacion', $datos);
    }

    /**
     * Imprimir Comprobante de Entrega de Reparación
     * CU-12 Paso 9: "Si el nuevo estado es 'Entregado', emite un comprobante interno de entrega"
     * 
     * @param int $id ID de la reparación
     * @param ComprobanteService $service Servicio para preparar datos
     * @return \Illuminate\View\View Vista del comprobante lista para imprimir
     */
    public function imprimirComprobanteEntrega($id, ComprobanteService $service)
    {
        $reparacion = Reparacion::with([
            'cliente', 
            'tecnico', 
            'estado', 
            'modelo.marca',
            'repuestos.producto'
        ])->findOrFail($id);

        // Preparar datos siguiendo lineamientos de Kendall (Información constante vs variable)
        $datos = $service->prepararDatosComprobanteEntrega($reparacion);

        // Retornar vista Blade optimizada para impresión con window.print()
        return view('comprobantes.comprobante-entrega-reparacion', $datos);
    }

    /**
     * CU-12 (Parte 1): Formulario de Edición
     */
    public function edit($id): Response
    {
        // 1. Cargamos modelo.marca para pre-llenar los selects
        $reparacion = Reparacion::with(['cliente', 'repuestos.producto', 'modelo.marca'])->findOrFail($id);

        // Lógica de filtrado de productos (Repuestos/Insumos)
        $categoriasRepuestos = \App\Models\CategoriaProducto::where('nombre', 'like', '%Repuesto%')
            ->orWhere('nombre', 'like', '%Insumo%')
            ->pluck('id');

        $queryProductos = Producto::where('estadoProductoID', 1);

        if ($categoriasRepuestos->isNotEmpty()) {
            $queryProductos->whereIn('categoriaProductoID', $categoriasRepuestos);
        } else {
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
            'productos' => $queryProductos->orderBy('nombre')->get()->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'stock_total' => $p->stock_total 
            ]),
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * CU-12 (Parte 2): Procesar Actualización
     */
    public function update(Request $request, $id, ActualizarReparacionService $service): RedirectResponse
    {
        // Validamos también los campos del equipo por si se corrigieron
        $validated = $request->validate([
            'estado_reparacion_id' => 'required|exists:estados_reparacion,estadoReparacionID',
            'diagnostico_tecnico' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:1000',
            'tecnico_id' => 'nullable|exists:users,id',
            'costo_mano_obra' => 'nullable|numeric|min:0',
            'total_final' => 'nullable|numeric|min:0',
            
            // Campos de equipo (Opcionales en edición, pero si vienen se validan)
            'modelo_id' => 'nullable|exists:modelos,id',
            'numero_serie_imei' => 'nullable|string|max:100',
            'clave_bloqueo' => 'nullable|string|max:50',
            'accesorios_dejados' => 'nullable|string|max:500',
            'falla_declarada' => 'nullable|string|max:1000',
            'fecha_promesa' => 'nullable|date',

            // Repuestos
            'repuestos' => 'nullable|array',
            'repuestos.*.producto_id' => 'required|exists:productos,id',
            'repuestos.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            $reparacion = Reparacion::findOrFail($id);
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
     * CU-Anular: Anular Reparación
     */
    public function destroy(AnularReparacionRequest $request, $id, AnularReparacionService $service): RedirectResponse
    {
        try {
            $reparacion = Reparacion::with('repuestos.producto')->findOrFail($id);
            $service->handle($reparacion, $request->motivo, $request->user()->id);

            return redirect()->route('reparaciones.index')
                ->with('success', 'Reparación anulada y stock revertido.');
        } catch (\Exception $e) {
            Log::error("Error al anular reparación {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}