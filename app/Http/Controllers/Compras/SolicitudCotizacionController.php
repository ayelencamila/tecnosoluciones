<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Models\SolicitudCotizacion;
use App\Models\EstadoSolicitud;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Compras\SolicitudCotizacionService;
use App\Services\Compras\MonitoreoStockService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador: Solicitudes de Cotización (CU-20)
 * 
 * Gestión interna de solicitudes de cotización:
 * - CRUD de solicitudes
 * - Envío a proveedores
 * - Comparación de ofertas
 * - Monitoreo de stock
 * 
 * Lineamientos aplicados:
 * - Larman: Controller (maneja requests de usuario)
 * - Kendall: Flujo de proceso de cotización
 */
class SolicitudCotizacionController extends Controller
{
    protected SolicitudCotizacionService $solicitudService;
    protected MonitoreoStockService $monitoreoService;

    public function __construct(
        SolicitudCotizacionService $solicitudService,
        MonitoreoStockService $monitoreoService
    ) {
        $this->solicitudService = $solicitudService;
        $this->monitoreoService = $monitoreoService;
    }

    /**
     * Listado de solicitudes de cotización
     * 
     * GET /compras/solicitudes-cotizacion
     */
    public function index(Request $request): Response
    {
        $query = SolicitudCotizacion::with(['estado', 'usuario', 'detalles', 'cotizacionesProveedores.proveedor'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('codigo')) {
            $query->where('codigo_solicitud', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        $solicitudes = $query->paginate(15)->withQueryString();

        return Inertia::render('Compras/SolicitudesCotizacion/Index', [
            'solicitudes' => $solicitudes,
            'estados' => EstadoSolicitud::activos()->ordenados()->get(),
            'filtros' => $request->only(['codigo', 'estado_id', 'fecha_desde', 'fecha_hasta']),
            'resumenStock' => $this->monitoreoService->obtenerResumenStock(),
        ]);
    }

    /**
     * Formulario de nueva solicitud
     * 
     * GET /compras/solicitudes-cotizacion/create
     */
    public function create(): Response
    {
        // Obtener productos y proveedores para selectores
        $productos = Producto::with(['categoria', 'stocks'])
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->get()
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'stock_actual' => $producto->stock_total,
                ];
            });

        $proveedores = Proveedor::where('activo', true)
            ->orderBy('razon_social')
            ->get(['id', 'razon_social', 'email', 'telefono']);

        // Obtener productos bajo stock para sugerencia
        $productosBajoStock = $this->monitoreoService->detectarProductosBajoStock();

        return Inertia::render('Compras/SolicitudesCotizacion/Create', [
            'productos' => $productos,
            'proveedores' => $proveedores,
            'productosBajoStock' => $productosBajoStock,
        ]);
    }

    /**
     * Crear nueva solicitud
     * 
     * POST /compras/solicitudes-cotizacion
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_vencimiento' => 'required|date|after:today',
            'observaciones' => 'nullable|string|max:1000',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad_sugerida' => 'required|integer|min:1',
            'productos.*.observaciones' => 'nullable|string|max:500',
            'proveedores' => 'required|array|min:1',
            'proveedores.*' => 'exists:proveedores,id',
        ], [
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria',
            'fecha_vencimiento.after' => 'La fecha debe ser posterior a hoy',
            'productos.required' => 'Debe seleccionar al menos un producto',
            'productos.min' => 'Debe seleccionar al menos un producto',
            'proveedores.required' => 'Debe seleccionar al menos un proveedor',
            'proveedores.min' => 'Debe seleccionar al menos un proveedor',
        ]);

        try {
            $solicitud = $this->solicitudService->crearSolicitud(
                [
                    'fecha_vencimiento' => $validated['fecha_vencimiento'],
                    'observaciones' => $validated['observaciones'] ?? null,
                ],
                $validated['productos'],
                $validated['proveedores'],
                auth()->id()
            );

            return redirect()->route('solicitudes-cotizacion.show', $solicitud)
                ->with('success', "Solicitud {$solicitud->codigo_solicitud} creada exitosamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Ver detalle de solicitud
     * 
     * GET /compras/solicitudes-cotizacion/{solicitud}
     */
    public function show(SolicitudCotizacion $solicitud): Response
    {
        $solicitud->load([
            'estado',
            'usuario',
            'detalles.producto.categoria',
            'cotizacionesProveedores.proveedor',
            'cotizacionesProveedores.respuestas.producto',
        ]);

        // Obtener ranking si hay respuestas
        $ranking = $solicitud->cotizacionesProveedores()
            ->whereNotNull('fecha_respuesta')
            ->exists()
            ? $this->solicitudService->obtenerRankingOfertas($solicitud)
            : collect();

        return Inertia::render('Compras/SolicitudesCotizacion/Show', [
            'solicitud' => $solicitud,
            'ranking' => $ranking,
            'puedeEnviar' => $solicitud->puedeEnviarse() || 
                ($solicitud->estaEnviada() && $solicitud->cotizacionesProveedores()->where('estado_envio', 'Pendiente')->exists()),
        ]);
    }

    /**
     * Enviar solicitud a proveedores
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/enviar
     */
    public function enviar(Request $request, SolicitudCotizacion $solicitud): RedirectResponse
    {
        $validated = $request->validate([
            'canal' => 'required|in:whatsapp,email,ambos',
        ]);

        try {
            $resultado = $this->solicitudService->enviarSolicitudAProveedores(
                $solicitud,
                $validated['canal']
            );

            if ($resultado['enviados'] > 0) {
                return back()->with('success', $resultado['mensaje']);
            } else {
                return back()->with('warning', $resultado['mensaje']);
            }

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Agregar proveedor a solicitud existente
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/agregar-proveedor
     */
    public function agregarProveedor(Request $request, SolicitudCotizacion $solicitud): RedirectResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
        ]);

        try {
            $this->solicitudService->agregarProveedor($solicitud, $validated['proveedor_id']);

            return back()->with('success', 'Proveedor agregado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cerrar solicitud
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/cerrar
     */
    public function cerrar(SolicitudCotizacion $solicitud): RedirectResponse
    {
        try {
            $this->solicitudService->cerrarSolicitud($solicitud);

            return back()->with('success', "Solicitud {$solicitud->codigo_solicitud} cerrada");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancelar solicitud
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/cancelar
     */
    public function cancelar(SolicitudCotizacion $solicitud): RedirectResponse
    {
        try {
            $this->solicitudService->cancelarSolicitud($solicitud);

            return back()->with('success', "Solicitud {$solicitud->codigo_solicitud} cancelada");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generar solicitudes automáticas desde monitoreo de stock
     * 
     * POST /compras/solicitudes-cotizacion/generar-automaticas
     */
    public function generarAutomaticas(): RedirectResponse
    {
        try {
            $resultado = $this->monitoreoService->generarSolicitudesAutomaticas(
                auth()->id(),
                7 // días de vencimiento
            );

            if ($resultado['solicitudes_creadas'] > 0) {
                return redirect()->route('solicitudes-cotizacion.index')
                    ->with('success', "Se generaron {$resultado['solicitudes_creadas']} solicitud(es) automática(s)");
            } else {
                return back()->with('info', $resultado['mensaje']);
            }

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ver productos bajo stock
     * 
     * GET /compras/monitoreo-stock
     */
    public function monitoreoStock(): Response
    {
        $productosBajoStock = $this->monitoreoService->detectarProductosBajoStock();
        $productosAltaRotacion = $this->monitoreoService->detectarProductosAltaRotacion();
        $resumen = $this->monitoreoService->obtenerResumenStock();
        $porProveedor = $this->monitoreoService->agruparPorProveedor($productosBajoStock);

        return Inertia::render('Compras/MonitoreoStock/Index', [
            'productosBajoStock' => $productosBajoStock,
            'productosAltaRotacion' => $productosAltaRotacion,
            'porProveedor' => $porProveedor,
            'resumen' => $resumen,
        ]);
    }

    /**
     * Reenviar recordatorio a un proveedor específico
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/reenviar/{cotizacion}
     */
    public function reenviarRecordatorio(
        Request $request, 
        SolicitudCotizacion $solicitud, 
        \App\Models\CotizacionProveedor $cotizacion
    ): RedirectResponse {
        $validated = $request->validate([
            'canal' => 'required|in:whatsapp,email,ambos',
        ]);

        // Verificar que la cotización pertenece a la solicitud
        if ($cotizacion->solicitud_id !== $solicitud->id) {
            return back()->with('error', 'La cotización no pertenece a esta solicitud');
        }

        // Verificar que no ha respondido
        if ($cotizacion->fecha_respuesta || $cotizacion->motivo_rechazo) {
            return back()->with('error', 'Este proveedor ya respondió a la solicitud');
        }

        // Verificar que la solicitud no está vencida
        if ($solicitud->fecha_vencimiento < now()) {
            return back()->with('error', 'La solicitud ya venció');
        }

        try {
            $proveedor = $cotizacion->proveedor;

            if ($validated['canal'] === 'whatsapp' || $validated['canal'] === 'ambos') {
                \App\Jobs\EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion, true);
            }

            if ($validated['canal'] === 'email' || $validated['canal'] === 'ambos') {
                $proveedor->notify(new \App\Notifications\SolicitudCotizacionProveedor($cotizacion, true));
            }

            // Actualizar contadores
            $cotizacion->update([
                'recordatorios_enviados' => $cotizacion->recordatorios_enviados + 1,
                'ultimo_recordatorio' => now(),
            ]);

            return back()->with('success', "Recordatorio enviado a {$proveedor->razon_social}");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar recordatorio: ' . $e->getMessage());
        }
    }
}
