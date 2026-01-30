<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Venta;
use App\Models\Pago;
use App\Models\Reparacion;
use App\Models\OrdenCompra;
use App\Services\Comprobantes\ComprobanteService;
use App\Services\Comprobantes\RegistrarComprobanteService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CU-32: Gestionar Comprobantes Internos
 * Usa el modelo Comprobante existente con tipos_comprobante y estados_comprobante
 */
class ComprobanteInternoController extends Controller
{
    /**
     * Listado de comprobantes con filtros completos según CU-32
     */
    public function index(Request $request): Response
    {
        $query = Comprobante::with(['usuario', 'tipoComprobante', 'estadoComprobante'])
            ->orderBy('fecha_emision', 'desc');

        // Filtro por número
        if ($request->filled('buscar')) {
            $query->where('numero_correlativo', 'like', '%' . $request->buscar . '%');
        }

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo_comprobante_id', $request->tipo);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado_comprobante_id', $request->estado);
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        $comprobantes = $query->paginate(20)->withQueryString();

        // Enriquecer comprobantes con datos de la entidad relacionada y URL de impresión
        $comprobantes->getCollection()->transform(function ($comprobante) {
            $entidad = $this->getEntidadRelacionada($comprobante);
            if ($entidad) {
                $comprobante->cliente_nombre = $this->getClienteNombre($comprobante->tipo_entidad, $entidad);
                $comprobante->monto = $this->getMonto($comprobante->tipo_entidad, $entidad);
                $comprobante->url_impresion = $this->generarUrlImpresion($comprobante, $entidad);
            }
            return $comprobante;
        });

        // Obtener tipos y estados de las tablas (sin hardcodeo)
        $tipos = DB::table('tipos_comprobante')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['tipo_id', 'nombre', 'codigo']);

        $estados = DB::table('estados_comprobante')
            ->where('activo', true)
            ->get(['estado_id', 'nombre']);

        return Inertia::render('Comprobantes/Index', [
            'comprobantes' => $comprobantes,
            'filtros' => $request->only(['buscar', 'tipo', 'estado', 'fecha_desde', 'fecha_hasta']),
            'tipos' => $tipos,
            'estados' => $estados,
        ]);
    }

    /**
     * Detalle del comprobante con información enriquecida
     */
    public function show(Comprobante $comprobante): Response
    {
        $comprobante->load(['usuario', 'tipoComprobante', 'estadoComprobante', 'original', 'reemisiones']);

        // Determinar si puede anularse o reemitirse
        $estadoEmitido = DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');

        // Obtener datos de la entidad relacionada
        $entidad = $this->getEntidadRelacionada($comprobante);
        $datosEntidad = $entidad ? $this->getDatosEntidad($comprobante->tipo_entidad, $entidad) : null;

        // Generar URL de impresión según el tipo de comprobante
        $urlImpresion = $this->generarUrlImpresion($comprobante, $entidad);

        return Inertia::render('Comprobantes/Show', [
            'comprobante' => $comprobante,
            'datosEntidad' => $datosEntidad,
            'urlImpresion' => $urlImpresion,
            'puedeAnularse' => $comprobante->estado_comprobante_id === $estadoEmitido,
            'puedeReemitirse' => $comprobante->estado_comprobante_id === $estadoEmitido,
        ]);
    }

    /**
     * Genera la URL de impresión según el tipo de comprobante
     */
    protected function generarUrlImpresion(Comprobante $comprobante, $entidad): ?string
    {
        if (!$entidad) return null;

        $codigoTipo = $comprobante->tipoComprobante->codigo ?? '';

        return match($codigoTipo) {
            'TICKET' => route('ventas.imprimir', $entidad->ventaID ?? $entidad->venta_id),
            'RECIBO_PAGO' => route('pagos.imprimir', $entidad->pagoID ?? $entidad->pago_id),
            'INGRESO_REPARACION' => route('reparaciones.imprimir-ingreso', $entidad->reparacionID ?? $entidad->reparacion_id),
            'ENTREGA_REPARACION' => route('reparaciones.imprimir-entrega', $entidad->reparacionID ?? $entidad->reparacion_id),
            'NOTA_CREDITO_INTERNA' => route('ventas.imprimir-anulacion', $entidad->ventaID ?? $entidad->venta_id),
            'ORDEN_COMPRA' => route('ordenes.descargar-pdf', $entidad->id),
            default => null,
        };
    }

    /**
     * Ver PDF del comprobante - redirige a la vista de impresión correspondiente
     * CU-32 Paso 5a: "El sistema muestra la vista previa del comprobante en formato PDF"
     */
    public function verPdf(Comprobante $comprobante, ComprobanteService $service, RegistrarComprobanteService $registrarService)
    {
        // Registrar la visualización en auditoría
        $registrarService->registrarVisualizacion($comprobante, 'VER_PDF');

        // Obtener la entidad relacionada
        $entidad = $this->getEntidadRelacionada($comprobante);
        
        if (!$entidad) {
            return back()->with('error', 'No se encontró la entidad relacionada con este comprobante.');
        }

        // Redirigir a la vista de impresión correcta según el tipo
        $codigoTipo = $comprobante->tipoComprobante->codigo ?? '';

        switch ($codigoTipo) {
            case 'TICKET':
                return redirect()->route('ventas.imprimir', $entidad->ventaID);
            
            case 'RECIBO_PAGO':
                return redirect()->route('pagos.imprimir', $entidad->pagoID);
            
            case 'INGRESO_REPARACION':
                return redirect()->route('reparaciones.imprimir-ingreso', $entidad->reparacionID);
            
            case 'ENTREGA_REPARACION':
                return redirect()->route('reparaciones.imprimir-entrega', $entidad->reparacionID);
            
            case 'NOTA_CREDITO_INTERNA':
                return redirect()->route('ventas.imprimir-anulacion', $entidad->ventaID);
            
            case 'ORDEN_COMPRA':
                return redirect()->route('ordenes.descargar-pdf', $entidad->id);
            
            default:
                return back()->with('error', 'Tipo de comprobante no soportado para visualización.');
        }
    }

    /**
     * Descargar PDF del comprobante
     * CU-32 Paso 5b: "El sistema descarga el archivo PDF"
     */
    public function descargarPdf(Comprobante $comprobante, RegistrarComprobanteService $registrarService)
    {
        // Registrar la descarga en auditoría
        $registrarService->registrarVisualizacion($comprobante, 'DESCARGAR_PDF');

        // Por ahora redirigimos a ver PDF (las vistas blade tienen window.print)
        // En futuro se puede implementar descarga real con dompdf
        return $this->verPdf($comprobante, app(ComprobanteService::class), $registrarService);
    }

    /**
     * Anular comprobante
     * CU-32 Pasos 6a-6c: Anular comprobante con motivo
     */
    public function anular(Request $request, Comprobante $comprobante): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Debe indicar un motivo para la anulación.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        try {
            $comprobante->anular($request->motivo);

            return redirect()->route('comprobantes.show', $comprobante)
                ->with('success', 'Comprobante anulado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al anular comprobante: {$e->getMessage()}");
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reemitir comprobante
     * CU-32 Pasos 7a-7c: Reemitir comprobante con motivo
     */
    public function reemitir(Request $request, Comprobante $comprobante): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Debe indicar un motivo para la reemisión.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        try {
            $nuevo = $comprobante->reemitir(auth()->id(), $request->motivo);

            return redirect()->route('comprobantes.show', $nuevo)
                ->with('success', "Comprobante reemitido. Nuevo número: {$nuevo->numero_correlativo}");
        } catch (\Exception $e) {
            Log::error("Error al reemitir comprobante: {$e->getMessage()}");
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Obtiene la entidad relacionada (Venta, Pago, Reparación)
     */
    protected function getEntidadRelacionada(Comprobante $comprobante)
    {
        $tipoEntidad = $comprobante->tipo_entidad;
        $entidadId = $comprobante->entidad_id;

        if (!$tipoEntidad || !$entidadId) {
            return null;
        }

        try {
            return $tipoEntidad::find($entidadId);
        } catch (\Exception $e) {
            Log::warning("No se pudo cargar entidad relacionada: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Obtiene el nombre del cliente según el tipo de entidad
     */
    protected function getClienteNombre(string $tipoEntidad, $entidad): ?string
    {
        try {
            switch ($tipoEntidad) {
                case 'App\Models\Venta':
                    $entidad->loadMissing('cliente');
                    return $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : null;
                
                case 'App\Models\Pago':
                    $entidad->loadMissing('cliente');
                    return $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : null;
                
                case 'App\Models\Reparacion':
                    $entidad->loadMissing('cliente');
                    return $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : null;
                
                case 'App\Models\OrdenCompra':
                    $entidad->loadMissing('proveedor');
                    return $entidad->proveedor ? $entidad->proveedor->razon_social : null;
                
                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene el monto según el tipo de entidad
     */
    protected function getMonto(string $tipoEntidad, $entidad): ?float
    {
        try {
            switch ($tipoEntidad) {
                case 'App\Models\Venta':
                    return $entidad->total ?? null;
                
                case 'App\Models\Pago':
                    return $entidad->monto ?? null;
                
                case 'App\Models\Reparacion':
                    return $entidad->costoTotal ?? null;
                
                case 'App\Models\OrdenCompra':
                    return $entidad->total_final ?? null;
                
                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene datos completos de la entidad para el detalle
     */
    protected function getDatosEntidad(string $tipoEntidad, $entidad): array
    {
        $datos = [
            'tipo' => class_basename($tipoEntidad),
            'id' => $entidad->getKey(),
        ];

        try {
            switch ($tipoEntidad) {
                case 'App\Models\Venta':
                    $entidad->loadMissing(['cliente', 'vendedor', 'estado']);
                    $datos['cliente'] = $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : 'N/A';
                    $datos['vendedor'] = $entidad->vendedor ? $entidad->vendedor->name : 'N/A';
                    $datos['estado'] = $entidad->estado ? $entidad->estado->nombreEstado : 'N/A';
                    $datos['total'] = $entidad->total;
                    $datos['numero'] = $entidad->numeroComprobante;
                    $datos['ruta_ver'] = route('ventas.show', $entidad->ventaID);
                    break;
                
                case 'App\Models\Pago':
                    $entidad->loadMissing(['cliente', 'cajero', 'medioPago']);
                    $datos['cliente'] = $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : 'N/A';
                    $datos['cajero'] = $entidad->cajero ? $entidad->cajero->name : 'N/A';
                    $datos['medio_pago'] = $entidad->medioPago ? $entidad->medioPago->nombre : 'N/A';
                    $datos['monto'] = $entidad->monto;
                    $datos['numero'] = $entidad->numero_recibo;
                    $datos['ruta_ver'] = route('pagos.show', $entidad->pagoID);
                    break;
                
                case 'App\Models\Reparacion':
                    $entidad->loadMissing(['cliente', 'tecnico', 'estado']);
                    $datos['cliente'] = $entidad->cliente ? ($entidad->cliente->nombre . ' ' . $entidad->cliente->apellido) : 'N/A';
                    $datos['tecnico'] = $entidad->tecnico ? $entidad->tecnico->name : 'N/A';
                    $datos['estado'] = $entidad->estado ? $entidad->estado->nombre : 'N/A';
                    $datos['costo_total'] = $entidad->costoTotal;
                    $datos['codigo'] = $entidad->codigoReparacion;
                    $datos['ruta_ver'] = route('reparaciones.show', $entidad->reparacionID);
                    break;
                
                case 'App\Models\OrdenCompra':
                    $entidad->loadMissing(['proveedor', 'estado', 'usuario']);
                    $datos['proveedor'] = $entidad->proveedor ? $entidad->proveedor->razon_social : 'N/A';
                    $datos['usuario'] = $entidad->usuario ? $entidad->usuario->name : 'N/A';
                    $datos['estado'] = $entidad->estado ? $entidad->estado->nombre : 'N/A';
                    $datos['total'] = $entidad->total_final;
                    $datos['numero'] = $entidad->numero_oc;
                    $datos['ruta_ver'] = route('ordenes.show', $entidad->id);
                    break;
            }
        } catch (\Exception $e) {
            Log::warning("Error al obtener datos de entidad: {$e->getMessage()}");
        }

        return $datos;
    }
}
