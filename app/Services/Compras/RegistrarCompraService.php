<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use App\Models\CotizacionProveedor;
use App\Models\EstadoOrdenCompra;
use App\Models\Auditoria;
use App\Models\User;
use App\Jobs\EnviarOrdenCompraWhatsApp;
use App\Notifications\OrdenCompraGenerada;
use App\Notifications\OrdenCompraProveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

/**
 * Servicio CU-22: Generar y Enviar Orden de Compra
 * 
 * MODELO SIMPLIFICADO (sin tabla ofertas_compra):
 * SolicitudCotizacion → CotizacionProveedor (elegida) → OrdenCompra
 * 
 * Lineamientos aplicados:
 * - Larman: Experto en información (responsabilidad única)
 * - Kendall: Trazabilidad completa y generación de documentos
 * - Elmasri: Transacciones atómicas con bloqueo pesimista
 * 
 * @see docs/diagrams/CU-22-Generar-OrdenCompra.puml
 */
class RegistrarCompraService
{
    /**
     * Ejecuta el flujo CU-22: Genera OC desde cotización elegida.
     * 
     * Flujo Principal:
     * 1-2. Validar cotización elegida
     * 3-4. Generar cabecera y detalles de OC
     * 5. Generar PDF
     * 6. Enviar WhatsApp al proveedor
     * 7. Notificar por email
     * 8. Registrar auditoría
     *
     * @param int $cotizacionId ID de la cotización elegida
     * @param int $usuarioId ID del administrador que genera la OC
     * @param string $observaciones Instrucciones o notas para el proveedor
     * @return array ['orden' => OrdenCompra, 'advertencias' => array]
     * @throws Exception Si la cotización no está elegida o ya tiene OC
     */
    public function ejecutar(int $cotizacionId, int $usuarioId, string $observaciones): array
    {
        $advertencias = [];

        $orden = DB::transaction(function () use ($cotizacionId, $usuarioId, $observaciones, &$advertencias) {
            
            // Paso 1-2: VALIDAR COTIZACIÓN
            $cotizacion = $this->validarCotizacion($cotizacionId);
            
            // Paso 3-4: GENERAR ORDEN DE COMPRA
            $orden = $this->crearOrdenDesdeCotizacion($cotizacion, $usuarioId, $observaciones);
            
            // Paso 5: GENERAR PDF
            $pdfGenerado = $this->generarPdf($orden);
            if (!$pdfGenerado) {
                $advertencias[] = [
                    'tipo' => 'warning',
                    'mensaje' => 'El PDF no pudo generarse. La orden se registró con estado "Pendiente de Documento".',
                    'excepcion' => '8a'
                ];
            }
            
            // Paso 6: ENVIAR WHATSAPP AL PROVEEDOR
            $whatsappEnviado = $this->enviarWhatsApp($orden);
            if (!$whatsappEnviado) {
                $advertencias[] = [
                    'tipo' => 'warning',
                    'mensaje' => 'El envío por WhatsApp falló. La orden se marcó como "Envío Fallido". Puede reenviar manualmente.',
                    'excepcion' => '9a'
                ];
            }
            
            // Paso 7: NOTIFICAR POR EMAIL
            $emailEnviado = $this->enviarEmail($orden, $usuarioId);
            if (!$emailEnviado) {
                $advertencias[] = [
                    'tipo' => 'warning',
                    'mensaje' => 'El envío por Email falló. El proveedor no recibirá copia por correo.',
                    'excepcion' => '9b'
                ];
            }
            
            // Paso 8: AUDITORÍA
            try {
                Auditoria::registrar(
                    accion: Auditoria::ACCION_GENERAR_ORDEN_COMPRA,
                    tabla: 'ordenes_compra',
                    registroId: $orden->id,
                    motivo: $observaciones,
                    detalles: "OC {$orden->numero_oc} generada. Proveedor: {$cotizacion->proveedor->razon_social}. Total: \${$orden->total_final}",
                    usuarioId: $usuarioId
                );
            } catch (Exception $e) {
                $advertencias[] = [
                    'tipo' => 'info',
                    'mensaje' => 'La auditoría no pudo registrarse, pero la orden fue generada correctamente.',
                    'excepcion' => '11a',
                    'detalle' => $e->getMessage()
                ];
            }

            return $orden->fresh(['proveedor', 'cotizacionProveedor', 'detalles', 'estado']);
        });

        return [
            'orden' => $orden,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Valida que la cotización cumpla requisitos para generar OC
     */
    protected function validarCotizacion(int $cotizacionId): CotizacionProveedor
    {
        $cotizacion = CotizacionProveedor::with(['solicitud.detalles', 'proveedor', 'respuestas'])
            ->lockForUpdate()
            ->findOrFail($cotizacionId);

        // Ya tiene OC
        if ($cotizacion->ordenCompra()->exists()) {
            throw new Exception("Esta cotización ya tiene una Orden de Compra asociada.");
        }

        // No está elegida
        if (!$cotizacion->elegida) {
            throw new Exception("Solo se puede generar OC de cotizaciones elegidas.");
        }

        return $cotizacion;
    }

    /**
     * Crea la orden de compra con sus detalles desde la cotización
     */
    protected function crearOrdenDesdeCotizacion(CotizacionProveedor $cotizacion, int $usuarioId, string $observaciones): OrdenCompra
    {
        // Calcular total desde respuestas o usar total_estimado
        $total = $cotizacion->total_estimado ?: $cotizacion->totalCotizado();
        
        // Crear cabecera
        $orden = OrdenCompra::create([
            'numero_oc'              => OrdenCompra::generarNumeroOC(),
            'proveedor_id'           => $cotizacion->proveedor_id,
            'cotizacion_proveedor_id'=> $cotizacion->id,
            'user_id'                => $usuarioId,
            'estado_id'              => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::BORRADOR),
            'total_final'            => $total,
            'fecha_emision'          => now(),
            'observaciones'          => $observaciones,
        ]);

        // Crear detalles desde las respuestas del proveedor
        $respuestas = $cotizacion->respuestas;
        
        if ($respuestas->isEmpty()) {
            // Fallback: usar detalles de la solicitud
            $this->crearDetallesDesdeSolicitud($orden, $cotizacion);
        } else {
            // Caso ideal: usar respuestas del proveedor
            foreach ($respuestas as $respuesta) {
                $orden->detalles()->create([
                    'producto_id'      => $respuesta->producto_id,
                    'cantidad_pedida'  => $respuesta->cantidad_disponible,
                    'cantidad_recibida'=> 0,
                    'precio_unitario'  => $respuesta->precio_unitario,
                ]);
            }
        }

        return $orden;
    }

    /**
     * Fallback: Crear detalles desde la solicitud original
     */
    protected function crearDetallesDesdeSolicitud(OrdenCompra $orden, CotizacionProveedor $cotizacion): void
    {
        $solicitud = $cotizacion->solicitud;
        
        if (!$solicitud || $solicitud->detalles->isEmpty()) {
            throw new Exception("La cotización no tiene detalles para generar la orden.");
        }
        
        $total = $cotizacion->total_estimado ?: 0;
        $totalCantidad = $solicitud->detalles->sum('cantidad_sugerida');
        $precioPromedio = $totalCantidad > 0 ? ($total / $totalCantidad) : 0;
        
        foreach ($solicitud->detalles as $detalle) {
            $orden->detalles()->create([
                'producto_id'      => $detalle->producto_id,
                'cantidad_pedida'  => $detalle->cantidad_sugerida,
                'cantidad_recibida'=> 0,
                'precio_unitario'  => round($precioPromedio, 2),
            ]);
        }
    }

    /**
     * Genera el PDF de la orden de compra (CU-22 Paso 8)
     * Excepción 8a: Si falla, marca orden como "Pendiente de Documento"
     * 
     * @return bool True si se generó correctamente, False si falló
     */
    protected function generarPdf(OrdenCompra $orden): bool
    {
        try {
            // Cargar relaciones necesarias para el PDF
            $orden->load([
                'proveedor.direccion',
                'cotizacionProveedor.solicitud',
                'detalles.producto',
                'estado',
                'usuario',
            ]);
            
            // Generar PDF usando la vista blade
            $pdf = Pdf::loadView('pdf.orden-compra', [
                'orden' => $orden,
            ]);

            // Crear directorio si no existe
            $directorio = 'ordenes_compra';
            Storage::disk('public')->makeDirectory($directorio);

            // Nombre único: OC-20250115-001.pdf
            $nombreArchivo = "{$orden->numero_oc}.pdf";
            $rutaRelativa = "{$directorio}/{$nombreArchivo}";
            
            // Guardar PDF
            Storage::disk('public')->put($rutaRelativa, $pdf->output());

            // Actualizar orden con ruta del PDF
            $orden->update(['archivo_pdf' => $rutaRelativa]);

            Log::info("📄 PDF generado: {$orden->numero_oc}", ['ruta' => $rutaRelativa]);
            return true;
            
        } catch (Exception $e) {
            // Excepción 8a: Error al crear documento
            Log::error("❌ Excepción 8a - Error generando PDF para OC {$orden->numero_oc}: " . $e->getMessage());
            
            // Cambiar estado a "Pendiente de Documento"
            $estadoPendiente = EstadoOrdenCompra::where('nombre', 'Pendiente de Documento')->first();
            if ($estadoPendiente) {
                $orden->update(['estado_id' => $estadoPendiente->id]);
            }
            
            $this->registrarAlertaInterna($orden, 'Error al generar PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía WhatsApp al proveedor con la OC (CU-22 Paso 9)
     * Excepción 9a: Si falla, marca como "Envío Fallido"
     * 
     * @return bool True si se encoló correctamente, False si falló
     */
    protected function enviarWhatsApp(OrdenCompra $orden): bool
    {
        try {
            $proveedor = $orden->proveedor;
            
            // Validar que el proveedor tenga WhatsApp
            if (!$proveedor->whatsapp && !$proveedor->telefono) {
                Log::warning("⚠️ Excepción 9a - Proveedor {$proveedor->razon_social} sin WhatsApp/teléfono.");
                
                $estadoFallido = EstadoOrdenCompra::where('nombre', 'Envío Fallido')->first();
                if ($estadoFallido) {
                    $orden->update(['estado_id' => $estadoFallido->id]);
                }
                
                $this->registrarAlertaInterna($orden, 'Proveedor sin teléfono registrado');
                return false;
            }

            // Dispatch del Job (envío asíncrono con reintentos)
            EnviarOrdenCompraWhatsApp::dispatch($orden)
                ->onQueue('whatsapp');

            Log::info("📱 Job WhatsApp encolado para OC {$orden->numero_oc}");
            return true;
            
        } catch (Exception $e) {
            // Excepción 9a: Falla en el envío por WhatsApp
            Log::error("❌ Excepción 9a - Error encolando WhatsApp: " . $e->getMessage());
            
            $estadoFallido = EstadoOrdenCompra::where('nombre', 'Envío Fallido')->first();
            if ($estadoFallido) {
                $orden->update(['estado_id' => $estadoFallido->id]);
            }
            
            $this->registrarAlertaInterna($orden, 'Error al enviar WhatsApp: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía emails de la OC (CU-22 Paso 8)
     * 
     * - Al proveedor: con PDF adjunto para confirmar la orden
     * - Al administrador: notificación de la OC generada
     * 
     * En desarrollo usa Mailpit (localhost:1025)
     * 
     * @return bool True si se envió correctamente al proveedor, False si falló
     */
    protected function enviarEmail(OrdenCompra $orden, int $usuarioId): bool
    {
        $emailProveedorEnviado = false;
        
        // 1. Email al PROVEEDOR (con PDF adjunto)
        try {
            $proveedor = $orden->proveedor;
            
            if ($proveedor && $proveedor->email) {
                $proveedor->notify(new OrdenCompraProveedor($orden));
                Log::info("📧 Email de OC enviado al proveedor {$proveedor->email}");
                $emailProveedorEnviado = true;
            } else {
                Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin email. OC no enviada por correo.");
            }
            
        } catch (Exception $e) {
            Log::warning("⚠️ Excepción 9b - No se pudo enviar email al proveedor: " . $e->getMessage());
        }

        // 2. Email al ADMINISTRADOR (notificación interna, no afecta el resultado)
        try {
            $usuario = User::find($usuarioId);
            
            if ($usuario && $usuario->email) {
                $usuario->notify(new OrdenCompraGenerada($orden));
                Log::info("📧 Email de notificación enviado al admin {$usuario->email}");
            }
            
        } catch (Exception $e) {
            Log::warning("⚠️ No se pudo enviar email al administrador: " . $e->getMessage());
        }
        
        return $emailProveedorEnviado;
    }

    /**
     * Registra una alerta interna cuando hay problemas de envío (Excepción 11a)
     */
    protected function registrarAlertaInterna(OrdenCompra $orden, string $motivo): void
    {
        // Notificar a todos los administradores
        $admins = User::whereHas('roles', fn($q) => $q->where('nombre', 'Administrador'))->get();
        
        foreach ($admins as $admin) {
            $admin->notify(new OrdenCompraGenerada($orden, esAlerta: true, motivoAlerta: $motivo));
        }

        Log::alert("🚨 Alerta interna: OC {$orden->numero_oc} - {$motivo}");
    }

    /**
     * Reenvía la OC por WhatsApp (para reintentos manuales)
     */
    public function reenviarWhatsApp(OrdenCompra $orden): void
    {
        // Resetear estado si estaba fallido
        if ($orden->tieneEstado(EstadoOrdenCompra::ENVIO_FALLIDO)) {
            $orden->update([
                'estado_id' => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::BORRADOR)
            ]);
        }

        $this->enviarWhatsApp($orden);
    }

    /**
     * Reenvía la OC por Email al proveedor (para reintentos manuales)
     */
    public function reenviarEmail(OrdenCompra $orden): void
    {
        $proveedor = $orden->proveedor;
        
        if (!$proveedor || !$proveedor->email) {
            throw new Exception("El proveedor no tiene email registrado.");
        }

        $proveedor->notify(new OrdenCompraProveedor($orden));
        Log::info("📧 Email reenviado al proveedor {$proveedor->email} - OC {$orden->numero_oc}");
    }

    /**
     * Regenera el PDF de una orden existente
     */
    public function regenerarPdf(OrdenCompra $orden): void
    {
        $this->generarPdf($orden);
    }
}