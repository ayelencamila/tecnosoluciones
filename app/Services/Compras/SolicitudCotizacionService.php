<?php

namespace App\Services\Compras;

use App\Models\SolicitudCotizacion;
use App\Models\DetalleSolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Models\RespuestaCotizacion;
use App\Models\EstadoSolicitud;
use App\Models\Proveedor;
use App\Jobs\EnviarSolicitudCotizacionWhatsApp;
use App\Jobs\EnviarSolicitudCotizacionEmail;
use App\Notifications\SolicitudCotizacionProveedor;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio: Gestión de Solicitudes de Cotización (CU-20)
 * 
 * Responsabilidades:
 * - Crear solicitudes de cotización manuales
 * - Enviar Magic Links a proveedores (WhatsApp/Email)
 * - Procesar respuestas de proveedores
 * - Comparar ofertas y rankear
 * - Cerrar solicitudes vencidas
 * 
 * Lineamientos aplicados:
 * - Larman: Controller (orquesta flujo de cotización)
 * - Kendall: Automatización de proceso de compras
 */
class SolicitudCotizacionService
{
    /**
     * Crea una nueva solicitud de cotización manual
     * 
     * @param array $datos Datos de la solicitud
     * @param array $productos Array de productos [{producto_id, cantidad_sugerida, observaciones}]
     * @param array $proveedoresIds IDs de proveedores a invitar
     * @param int $userId Usuario que crea
     * @return SolicitudCotizacion
     */
    public function crearSolicitud(
        array $datos,
        array $productos,
        array $proveedoresIds,
        int $userId
    ): SolicitudCotizacion {
        $estadoAbierta = EstadoSolicitud::abierta();
        
        if (!$estadoAbierta) {
            throw new \Exception('No se encontró el estado "Abierta" en estados_solicitud');
        }

        DB::beginTransaction();
        
        try {
            // Crear solicitud
            $solicitud = SolicitudCotizacion::create([
                'codigo_solicitud' => SolicitudCotizacion::generarCodigoSolicitud(),
                'fecha_emision' => now(),
                'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? now()->addDays(7),
                'estado_id' => $estadoAbierta->id,
                'user_id' => $userId,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // Crear detalles
            foreach ($productos as $producto) {
                DetalleSolicitudCotizacion::create([
                    'solicitud_id' => $solicitud->id,
                    'producto_id' => $producto['producto_id'],
                    'cantidad_sugerida' => $producto['cantidad_sugerida'],
                    'observaciones' => $producto['observaciones'] ?? null,
                ]);
            }

            // Crear cotizaciones para proveedores seleccionados
            foreach ($proveedoresIds as $proveedorId) {
                CotizacionProveedor::create([
                    'solicitud_id' => $solicitud->id,
                    'proveedor_id' => $proveedorId,
                    'estado_envio' => 'Pendiente',
                ]);
            }

            DB::commit();

            return $solicitud->load(['detalles.producto', 'cotizacionesProveedores.proveedor']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Agrega un proveedor a una solicitud existente
     */
    public function agregarProveedor(SolicitudCotizacion $solicitud, int $proveedorId): CotizacionProveedor
    {
        // Verificar que la solicitud esté abierta
        if (!$solicitud->estaAbierta() && !$solicitud->estaEnviada()) {
            throw new \Exception('Solo se pueden agregar proveedores a solicitudes abiertas o enviadas');
        }

        // Verificar que el proveedor no esté ya
        if ($solicitud->cotizacionesProveedores()->where('proveedor_id', $proveedorId)->exists()) {
            throw new \Exception('Este proveedor ya fue agregado a la solicitud');
        }

        return CotizacionProveedor::create([
            'solicitud_id' => $solicitud->id,
            'proveedor_id' => $proveedorId,
            'estado_envio' => 'Pendiente',
        ]);
    }

    /**
     * Envía los Magic Links a todos los proveedores pendientes
     * 
     * @param SolicitudCotizacion $solicitud
     * @param string $canal 'whatsapp', 'email', 'ambos' o 'inteligente'
     * @return array Resultado del envío
     */
    public function enviarSolicitudAProveedores(
        SolicitudCotizacion $solicitud, 
        string $canal = 'inteligente'
    ): array {
        if (!$solicitud->puedeEnviarse() && !$solicitud->estaEnviada()) {
            throw new \Exception('La solicitud no puede enviarse (sin productos, vencida o ya cerrada)');
        }

        $cotizacionesPendientes = $solicitud->cotizacionesProveedores()
            ->where('estado_envio', 'Pendiente')
            ->with('proveedor')
            ->get();

        $enviados = 0;
        $enviadosWhatsApp = 0;
        $enviadosEmail = 0;
        $errores = [];

        foreach ($cotizacionesPendientes as $cotizacion) {
            try {
                $proveedor = $cotizacion->proveedor;
                $tieneWhatsApp = $proveedor->tieneWhatsApp();
                $tieneEmail = $proveedor->tieneEmail();
                
                // Determinar canales a usar según modo
                $enviarWhatsApp = false;
                $enviarEmail = false;
                
                if ($canal === 'inteligente') {
                    // Modo inteligente: enviar por todos los canales disponibles del proveedor
                    $enviarWhatsApp = $tieneWhatsApp;
                    $enviarEmail = $tieneEmail;
                    
                    // Si no tiene ninguno, marcar error
                    if (!$enviarWhatsApp && !$enviarEmail) {
                        throw new \Exception("Proveedor {$proveedor->razon_social} sin contacto válido (ni WhatsApp ni email)");
                    }
                } elseif ($canal === 'ambos') {
                    // Forzar ambos canales
                    $enviarWhatsApp = true;
                    $enviarEmail = true;
                } elseif ($canal === 'whatsapp') {
                    $enviarWhatsApp = true;
                } elseif ($canal === 'email') {
                    $enviarEmail = true;
                }

                // Despachar Jobs según canales determinados
                if ($enviarWhatsApp && $tieneWhatsApp) {
                    EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion);
                    $enviadosWhatsApp++;
                }

                if ($enviarEmail && $tieneEmail) {
                    EnviarSolicitudCotizacionEmail::dispatch($cotizacion);
                    $enviadosEmail++;
                }

                $enviados++;

            } catch (\Exception $e) {
                Log::error("Error enviando a proveedor {$cotizacion->proveedor_id}: " . $e->getMessage());
                $errores[] = [
                    'proveedor' => $cotizacion->proveedor->razon_social,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Cambiar estado a "Enviada" si había algo que enviar
        if ($solicitud->estaAbierta() && $enviados > 0) {
            $estadoEnviada = EstadoSolicitud::enviada();
            $solicitud->update(['estado_id' => $estadoEnviada->id]);
        }

        return [
            'enviados' => $enviados,
            'enviados_whatsapp' => $enviadosWhatsApp,
            'enviados_email' => $enviadosEmail,
            'errores' => $errores,
            'mensaje' => $enviados > 0 
                ? "Se enviaron {$enviados} solicitud(es): {$enviadosWhatsApp} WhatsApp, {$enviadosEmail} Email"
                : 'No había proveedores pendientes de envío',
        ];
    }

    /**
     * Registra la respuesta de un proveedor desde el portal
     * 
     * @param CotizacionProveedor $cotizacion
     * @param array $respuestas Array de [{producto_id, precio_unitario, cantidad_disponible, plazo_entrega_dias, observaciones}]
     * @return CotizacionProveedor
     */
    public function registrarRespuestaProveedor(
        CotizacionProveedor $cotizacion,
        array $respuestas
    ): CotizacionProveedor {
        if (!$cotizacion->puedeResponder()) {
            throw new \Exception('Esta cotización no puede ser respondida (ya respondió, no enviada o vencida)');
        }

        DB::beginTransaction();
        
        try {
            // Eliminar respuestas anteriores si hubiera (por si es un reenvío)
            $cotizacion->respuestas()->delete();

            // Registrar cada respuesta
            foreach ($respuestas as $respuesta) {
                RespuestaCotizacion::create([
                    'cotizacion_proveedor_id' => $cotizacion->id,
                    'producto_id' => $respuesta['producto_id'],
                    'precio_unitario' => $respuesta['precio_unitario'],
                    'cantidad_disponible' => $respuesta['cantidad_disponible'],
                    'plazo_entrega_dias' => $respuesta['plazo_entrega_dias'],
                    'observaciones' => $respuesta['observaciones'] ?? null,
                ]);
            }

            // Marcar como respondida
            $cotizacion->registrarRespuesta();

            // NOTA: Ya no creamos OfertaCompra - el modelo simplificado usa
            // directamente CotizacionProveedor → RespuestaCotizacion → OrdenCompra

            DB::commit();

            // Notificar a usuarios con permiso de compras
            $this->notificarRespuestaCotizacion($cotizacion);

            return $cotizacion->load('respuestas.producto');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Notifica a los usuarios de compras sobre la nueva respuesta
     */
    protected function notificarRespuestaCotizacion(CotizacionProveedor $cotizacion): void
    {
        try {
            // Obtener usuarios con permisos de compras
            $usuariosCompras = \App\Models\User::whereHas('rol', function ($query) {
                $query->whereIn('nombre', ['Administrador', 'Compras', 'Gerente']);
            })->get();

            if ($usuariosCompras->isNotEmpty()) {
                \Notification::send($usuariosCompras, new \App\Notifications\ProveedorRespondioCotizacion($cotizacion));
            }
        } catch (\Exception $e) {
            // Log error pero no fallar la operación principal
            \Log::warning("No se pudo enviar notificación de respuesta de cotización: " . $e->getMessage());
        }
    }

    /**
     * Registra el rechazo de un proveedor
     */
    public function registrarRechazoProveedor(
        CotizacionProveedor $cotizacion,
        string $motivo
    ): CotizacionProveedor {
        if (!$cotizacion->puedeResponder()) {
            throw new \Exception('Esta cotización no puede ser respondida');
        }

        $cotizacion->registrarRechazo($motivo);

        return $cotizacion;
    }

    /**
     * Obtiene el ranking de ofertas para una solicitud
     * Ordena por total más bajo
     * 
     * @param SolicitudCotizacion $solicitud
     * @return Collection
     */
    public function obtenerRankingOfertas(SolicitudCotizacion $solicitud): Collection
    {
        return $solicitud->cotizacionesProveedores()
            ->whereNotNull('fecha_respuesta')
            ->whereNull('motivo_rechazo')
            ->with(['proveedor', 'respuestas.producto'])
            ->get()
            ->map(function ($cotizacion) use ($solicitud) {
                $total = $cotizacion->totalCotizado();
                $productosRequeridos = $solicitud->detalles->count();
                $productosCotizados = $cotizacion->respuestas->count();
                $plazoMaximo = $cotizacion->respuestas->max('plazo_entrega_dias');
                
                return [
                    'cotizacion_id' => $cotizacion->id,
                    'proveedor' => $cotizacion->proveedor,
                    'total' => $total,
                    'productos_cotizados' => $productosCotizados,
                    'productos_requeridos' => $productosRequeridos,
                    'cotizo_completo' => $productosCotizados >= $productosRequeridos,
                    'plazo_maximo_dias' => $plazoMaximo,
                    'fecha_respuesta' => $cotizacion->fecha_respuesta,
                    'respuestas' => $cotizacion->respuestas,
                ];
            })
            ->sortBy('total')
            ->values();
    }

    /**
     * Cierra una solicitud (marca como cerrada)
     */
    public function cerrarSolicitud(SolicitudCotizacion $solicitud): SolicitudCotizacion
    {
        $estadoCerrada = EstadoSolicitud::cerrada();
        $solicitud->update(['estado_id' => $estadoCerrada->id]);
        
        return $solicitud;
    }

    /**
     * Cancela una solicitud
     */
    public function cancelarSolicitud(SolicitudCotizacion $solicitud): SolicitudCotizacion
    {
        if (!$solicitud->estaAbierta() && !$solicitud->estaEnviada()) {
            throw new \Exception('Solo se pueden cancelar solicitudes abiertas o enviadas');
        }

        $estadoCancelada = EstadoSolicitud::cancelada();
        $solicitud->update(['estado_id' => $estadoCancelada->id]);
        
        return $solicitud;
    }

    /**
     * Marca solicitudes vencidas (para cron job)
     */
    public function marcarSolicitudesVencidas(): int
    {
        $estadoVencida = EstadoSolicitud::vencida();
        
        // Buscar solicitudes que vencieron y están en Abierta o Enviada
        $vencidas = SolicitudCotizacion::where('fecha_vencimiento', '<', now())
            ->whereHas('estado', fn($q) => $q->whereIn('nombre', ['Abierta', 'Enviada']))
            ->get();

        foreach ($vencidas as $solicitud) {
            $solicitud->update(['estado_id' => $estadoVencida->id]);
            Log::info("⏰ Solicitud {$solicitud->codigo_solicitud} marcada como vencida");
        }

        return $vencidas->count();
    }
}
