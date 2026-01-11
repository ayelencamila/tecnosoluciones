<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compras\StoreOfertaRequest;
use App\Services\Compras\RegistrarOfertaService;
use App\Models\OfertaCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Auditoria;
use App\Models\EstadoOferta;
use App\Models\SolicitudCotizacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfertaCompraController extends Controller
{
    /**
     * CU-21 (Paso 1): Listar ofertas para gestión
     */
    public function index(Request $request): Response
    {
        // Usamos query scope (asumiendo que implementarás scopeSearch en el modelo OfertaCompra)
        // Si no, usa where simple por ahora.
        $query = OfertaCompra::with(['proveedor', 'estado', 'user'])
            ->latest('fecha_recepcion');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('codigo_oferta', 'like', "%{$term}%")
                  ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%{$term}%"));
            });
        }

        $ofertas = $query->paginate(10)->withQueryString();

        // Productos que tienen múltiples ofertas pendientes/pre-aprobadas (para comparar)
        $estadosPendientes = EstadoOferta::whereIn('nombre', ['Pendiente', 'Pre-aprobada'])->pluck('id');
        
        $productosConOfertas = Producto::select('productos.id', 'productos.nombre')
            ->join('detalle_ofertas_compra', 'productos.id', '=', 'detalle_ofertas_compra.producto_id')
            ->join('ofertas_compra', 'detalle_ofertas_compra.oferta_id', '=', 'ofertas_compra.id')
            ->whereIn('ofertas_compra.estado_id', $estadosPendientes)
            ->groupBy('productos.id', 'productos.nombre')
            ->havingRaw('COUNT(DISTINCT ofertas_compra.id) > 1')
            ->selectRaw('COUNT(DISTINCT ofertas_compra.id) as ofertas_count')
            ->orderBy('ofertas_count', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Compras/Ofertas/Index', [
            'ofertas' => $ofertas,
            'filters' => $request->only(['search']),
            'productosConOfertas' => $productosConOfertas,
        ]);
    }

    /**
     * CU-21 (Paso 2): Formulario de registro de oferta
     */
    public function create(Request $request): Response
    {
        // Si viene de una solicitud (CU-20), precargamos datos
        $solicitudId = $request->query('solicitud_id');
        
        // Obtenemos el ID del estado "Activo" para productos
        $estadoActivo = \App\Models\EstadoProducto::where('nombre', 'Activo')->first();
        
        return Inertia::render('Compras/Ofertas/Create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
            // Enviamos productos activos para el selector del array dinámico
            'productos' => Producto::when($estadoActivo, fn($q) => $q->where('estadoProductoID', $estadoActivo->id))
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get(),
            'solicitud_id' => $solicitudId,
        ]);
    }

    /**
     * CU-21 (Paso 14): Procesar y guardar la oferta
     */
    public function store(StoreOfertaRequest $request, RegistrarOfertaService $service): RedirectResponse
    {
        try {
            $oferta = $service->ejecutar($request->validated(), $request->user()->id);

            return redirect()->route('ofertas.show', $oferta->id)
                ->with('success', "Oferta {$oferta->codigo_oferta} registrada correctamente.");

        } catch (\Exception $e) {
            Log::error("Error al registrar oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Ocurrió un error inesperado al registrar la oferta. ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * CU-21 (Paso 11): Ver detalle y comparar
     */
    public function show($id): Response
    {
        $oferta = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado', 'user', 'solicitud'])
            ->findOrFail($id);

        return Inertia::render('Compras/Ofertas/Show', [
            'oferta' => $oferta
        ]);
    }

    /**
     * CU-21 (Paso 10): Vista de comparación de ofertas
     * Muestra un cuadro comparativo de ofertas para un mismo producto
     */
    public function comparar(Request $request): Response
    {
        $productoId = $request->query('producto_id');
        
        if (!$productoId) {
            // Sin producto específico, mostrar todas las ofertas pendientes/pre-aprobadas
            // agrupadas por producto para facilitar la comparación
            return redirect()->route('ofertas.index')
                ->with('info', 'Seleccione un producto para comparar ofertas.');
        }

        $producto = Producto::findOrFail($productoId);

        // Obtener todas las ofertas que contienen este producto y están pendientes o pre-aprobadas
        $estadosPendientes = EstadoOferta::whereIn('nombre', ['Pendiente', 'Pre-aprobada', 'Elegida'])
            ->pluck('id');

        $ofertas = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado'])
            ->whereIn('estado_id', $estadosPendientes)
            ->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('producto_id', $productoId);
            })
            ->orderBy('fecha_recepcion', 'desc')
            ->get();

        return Inertia::render('Compras/Ofertas/Comparar', [
            'producto' => $producto,
            'ofertas' => $ofertas,
            'filters' => $request->only(['producto_id']),
        ]);
    }

    /**
     * CU-21 (Paso 12): Elegir/Pre-aprobar una oferta
     * El Gerente selecciona la oferta ganadora
     */
    public function elegir(OfertaCompra $oferta): RedirectResponse
    {
        try {
            DB::transaction(function () use ($oferta) {
                // Marcar como elegida usando el método del modelo
                $oferta->elegir();

                // Registrar auditoría (Paso 13)
                Auditoria::create([
                    'accion' => Auditoria::ACCION_ELEGIR_OFERTA,
                    'tabla_afectada' => 'ofertas_compra',
                    'registro_id' => $oferta->id,
                    'user_id' => auth()->id(),
                    'motivo' => 'Oferta seleccionada para generar orden de compra',
                    'detalles_json' => json_encode([
                        'codigo_oferta' => $oferta->codigo_oferta,
                        'proveedor_id' => $oferta->proveedor_id,
                        'proveedor_nombre' => $oferta->proveedor->razon_social,
                        'total_estimado' => $oferta->total_estimado,
                        'estado_anterior' => 'Pendiente',
                        'estado_nuevo' => 'Elegida',
                    ]),
                    'fecha' => now(),
                ]);
            });

            return redirect()->back()
                ->with('success', "Oferta {$oferta->codigo_oferta} elegida correctamente. Ya puede generar la Orden de Compra.");

        } catch (\Exception $e) {
            Log::error("Error al elegir oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'No se pudo elegir la oferta: ' . $e->getMessage()]);
        }
    }

    /**
     * CU-21: Rechazar una oferta
     */
    public function rechazar(Request $request, OfertaCompra $oferta): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($oferta, $request) {
                $estadoAnterior = $oferta->estado->nombre;
                
                $oferta->rechazar($request->motivo);

                Auditoria::create([
                    'accion' => 'RECHAZAR_OFERTA',
                    'tabla_afectada' => 'ofertas_compra',
                    'registro_id' => $oferta->id,
                    'user_id' => auth()->id(),
                    'motivo' => $request->motivo,
                    'detalles_json' => json_encode([
                        'codigo_oferta' => $oferta->codigo_oferta,
                        'proveedor_id' => $oferta->proveedor_id,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => 'Rechazada',
                    ]),
                    'fecha' => now(),
                ]);
            });

            return redirect()->back()
                ->with('success', "Oferta {$oferta->codigo_oferta} rechazada.");

        } catch (\Exception $e) {
            Log::error("Error al rechazar oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'No se pudo rechazar la oferta: ' . $e->getMessage()]);
        }
    }
}