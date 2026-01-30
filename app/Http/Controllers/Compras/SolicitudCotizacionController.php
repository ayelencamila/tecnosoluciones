<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Models\SolicitudCotizacion;
use App\Models\EstadoSolicitud;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Configuracion;
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

        // Verificar si la generación automática está habilitada
        $generacionAutomatica = Configuracion::get('compras_generacion_automatica', 'false') === 'true';

        return Inertia::render('Compras/SolicitudesCotizacion/Index', [
            'solicitudes' => $solicitudes,
            'estados' => EstadoSolicitud::activos()->ordenados()->get(),
            'filtros' => $request->only(['codigo', 'estado_id', 'fecha_desde', 'fecha_hasta']),
            'resumenStock' => $this->monitoreoService->obtenerResumenStock(),
            'generacionAutomatica' => $generacionAutomatica,
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
     * Ver ranking completo de respuestas
     * 
     * GET /compras/solicitudes-cotizacion/{solicitud}/ranking
     */
    public function ranking(SolicitudCotizacion $solicitud): Response
    {
        $solicitud->load([
            'estado',
            'detalles.producto',
            'cotizacionesProveedores.proveedor',
            'cotizacionesProveedores.respuestas.producto',
        ]);

        // Obtener ranking ordenado de mejor a peor
        $ranking = $this->solicitudService->obtenerRankingOfertas($solicitud);

        return Inertia::render('Compras/SolicitudesCotizacion/Ranking', [
            'solicitud' => $solicitud,
            'ranking' => $ranking,
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

        // Verificar si la generación automática está habilitada
        $generacionAutomatica = Configuracion::get('compras_generacion_automatica', 'false') === 'true';

        return Inertia::render('Compras/MonitoreoStock/Index', [
            'productosBajoStock' => $productosBajoStock,
            'productosAltaRotacion' => $productosAltaRotacion,
            'porProveedor' => $porProveedor,
            'resumen' => $resumen,
            'generacionAutomatica' => $generacionAutomatica,
        ]);
    }

    /**
     * Elegir una cotización ganadora y opcionalmente generar orden de compra
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/elegir-cotizacion/{cotizacion}
     * 
     * Lineamiento CU-20/CU-22: Permite seleccionar la mejor oferta y continuar
     * al flujo de generación de orden de compra
     */
    public function elegirCotizacion(
        Request $request,
        SolicitudCotizacion $solicitud,
        \App\Models\CotizacionProveedor $cotizacion
    ): RedirectResponse {
        // Verificar que la cotización pertenece a la solicitud
        if ($cotizacion->solicitud_id !== $solicitud->id) {
            return back()->with('error', 'La cotización no pertenece a esta solicitud');
        }

        // Verificar que ha respondido
        if (!$cotizacion->fecha_respuesta) {
            return back()->with('error', 'El proveedor aún no ha respondido');
        }

        // Verificar que no ha sido rechazada
        if ($cotizacion->motivo_rechazo) {
            return back()->with('error', 'El proveedor rechazó esta solicitud');
        }

        try {
            // Marcar la cotización como elegida
            $cotizacion->update(['elegida' => true]);

            // Cerrar la solicitud si está abierta/enviada
            if (in_array($solicitud->estado->nombre, ['Abierta', 'Enviada'])) {
                $this->solicitudService->cerrarSolicitud($solicitud);
            }

            // Si el usuario quiere generar orden directamente
            if ($request->input('generar_orden')) {
                // Crear oferta desde la cotización para seguir el flujo CU-21/CU-22
                $oferta = $this->crearOfertaDesdeCotizacion($cotizacion);
                
                return redirect()->route('ordenes-compra.create', ['oferta_id' => $oferta->id])
                    ->with('success', "Cotización de {$cotizacion->proveedor->razon_social} elegida. Complete la orden de compra.");
            }

            return back()->with('success', "Cotización de {$cotizacion->proveedor->razon_social} elegida como ganadora.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al elegir cotización: ' . $e->getMessage());
        }
    }

    /**
     * Crear una OfertaCompra desde una CotizacionProveedor
     * para integrarse con el flujo existente de CU-21/CU-22
     */
    protected function crearOfertaDesdeCotizacion(\App\Models\CotizacionProveedor $cotizacion): \App\Models\OfertaCompra
    {
        $estadoElegida = \App\Models\EstadoOferta::where('nombre', 'Elegida')->first();
        
        // Generar código único
        $ultimoNumero = \App\Models\OfertaCompra::whereYear('created_at', now()->year)->max('id') ?? 0;
        $codigoOferta = 'OF-' . now()->format('Y') . '-' . str_pad($ultimoNumero + 1, 4, '0', STR_PAD_LEFT);

        // Calcular total
        $total = $cotizacion->respuestas->sum(function ($respuesta) {
            return ($respuesta->precio_unitario ?? 0) * ($respuesta->cantidad_disponible ?? $respuesta->cantidad_solicitada ?? 1);
        });

        // Crear la oferta
        $oferta = \App\Models\OfertaCompra::create([
            'codigo_oferta' => $codigoOferta,
            'proveedor_id' => $cotizacion->proveedor_id,
            'solicitud_id' => $cotizacion->solicitud_id,
            'cotizacion_proveedor_id' => $cotizacion->id,
            'estado_id' => $estadoElegida->id,
            'fecha_recepcion' => $cotizacion->fecha_respuesta,
            'fecha_validez' => $cotizacion->solicitud->fecha_vencimiento,
            'total_estimado' => $total,
            'observaciones' => 'Oferta generada automáticamente desde cotización elegida',
            'user_id' => auth()->id(),
        ]);

        // Crear detalles de la oferta
        foreach ($cotizacion->respuestas as $respuesta) {
            \App\Models\DetalleOferta::create([
                'oferta_id' => $oferta->id,
                'producto_id' => $respuesta->producto_id,
                'cantidad' => $respuesta->cantidad_disponible ?? $respuesta->cantidad_solicitada ?? 1,
                'precio_unitario' => $respuesta->precio_unitario ?? 0,
                'disponibilidad' => $respuesta->disponibilidad_inmediata ? 'inmediata' : 'a_pedido',
                'dias_entrega' => $respuesta->plazo_entrega_dias ?? 0,
            ]);
        }

        return $oferta;
    }

    /**
     * Elegir cotización y generar Orden de Compra automáticamente
     * 
     * POST /compras/solicitudes-cotizacion/{solicitud}/elegir-generar-orden/{cotizacion}
     * 
     * Este método implementa el flujo automático completo:
     * 1. Marca la cotización como elegida
     * 2. Crea la oferta de compra
     * 3. Genera la orden de compra
     * 4. Envía al proveedor por WhatsApp/Email
     * 5. Cierra la solicitud
     */
    public function elegirYGenerarOrden(
        Request $request,
        SolicitudCotizacion $solicitud,
        \App\Models\CotizacionProveedor $cotizacion
    ): Response {
        // Validaciones
        if ($cotizacion->solicitud_id !== $solicitud->id) {
            return back()->with('error', 'La cotización no pertenece a esta solicitud');
        }

        if (!$cotizacion->fecha_respuesta) {
            return back()->with('error', 'El proveedor aún no ha respondido');
        }

        if ($cotizacion->motivo_rechazo) {
            return back()->with('error', 'El proveedor rechazó esta solicitud');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Marcar la cotización como elegida
            $cotizacion->update(['elegida' => true]);

            // 2. Crear la oferta de compra
            $oferta = $this->crearOfertaDesdeCotizacion($cotizacion);

            // 3. Generar la orden de compra automáticamente
            $registrarCompraService = app(\App\Services\Compras\RegistrarCompraService::class);
            $motivo = $request->input('motivo', 'Seleccionada como mejor oferta del ranking automático');
            
            $resultado = $registrarCompraService->ejecutar(
                ofertaId: $oferta->id,
                usuarioId: auth()->id(),
                observaciones: $motivo
            );

            $orden = $resultado['orden'];

            // 4. Cerrar la solicitud
            if (in_array($solicitud->estado->nombre, ['Abierta', 'Enviada'])) {
                $this->solicitudService->cerrarSolicitud($solicitud);
            }

            \Illuminate\Support\Facades\DB::commit();

            // Retornar con los datos de la orden generada
            return Inertia::render('Compras/SolicitudesCotizacion/Ranking', [
                'solicitud' => $solicitud->fresh(['detalles.producto', 'cotizacionesProveedores.proveedor', 'cotizacionesProveedores.respuestas.producto']),
                'ranking' => $this->solicitudService->obtenerRankingOfertas($solicitud),
                'ordenGenerada' => [
                    'id' => $orden->id,
                    'numero_oc' => $orden->numero_oc,
                    'total' => $orden->total_final,
                    'proveedor' => $orden->proveedor->razon_social,
                    'estado' => $orden->estado->nombre,
                ],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Error al generar orden automática: " . $e->getMessage());
            
            return back()->with('error', 'Error al generar la orden de compra: ' . $e->getMessage());
        }
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

    /**
     * Elimina una solicitud de cotización
     * 
     * Validaciones:
     * - No permitir si ya hay Ofertas de Compra formales generadas
     * - No permitir si está en estado que requiere gestión activa
     * 
     * DELETE /solicitudes-cotizacion/{solicitud}
     */
    public function destroy(SolicitudCotizacion $solicitud): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            // VALIDACIÓN 1: Verificar si ya hay ofertas de compra generadas desde esta solicitud
            $ofertasGeneradas = \App\Models\OfertaCompra::where('solicitud_id', $solicitud->id)->count();
            
            if ($ofertasGeneradas > 0) {
                return back()->with('error', 
                    'No se puede eliminar esta solicitud porque ya tiene ' . 
                    $ofertasGeneradas . ' oferta(s) de compra registrada(s). ' .
                    'Debe eliminar primero las ofertas relacionadas.'
                );
            }
            
            // VALIDACIÓN 2: Verificar si el estado permite eliminación
            $estadosNoEliminables = ['Completada', 'Procesada'];
            if (in_array($solicitud->estado->nombre, $estadosNoEliminables)) {
                return back()->with('error', 
                    'No se puede eliminar una solicitud en estado "' . $solicitud->estado->nombre . '". ' .
                    'Solo se permiten eliminar solicitudes en estados tempranos.'
                );
            }
            
            // Eliminar cotizaciones de proveedores y sus respuestas asociadas
            foreach ($solicitud->cotizacionesProveedores as $cotizacion) {
                $cotizacion->respuestas()->delete();
                $cotizacion->delete();
            }
            
            // Eliminar detalles
            $solicitud->detalles()->delete();
            
            // Eliminar solicitud
            $solicitud->delete();
            
            DB::commit();
            
            return redirect()->route('solicitudes-cotizacion.index')
                ->with('success', 'Solicitud eliminada correctamente');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar la solicitud: ' . $e->getMessage());
        }
    }
}
