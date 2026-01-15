<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use App\Models\OfertaCompra;
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
     * Ejecuta el flujo CU-22: Genera OC desde oferta elegida.
     * 
     * Flujo Principal:
     * 1. Validar oferta elegida
     * 2. Generar cabecera y detalles de OC
     * 3. Generar PDF
     * 4. Enviar WhatsApp al proveedor (async)
     * 5. Notificar por email al administrador (async)
     * 6. Registrar auditoría
     *
     * @param int $ofertaId ID de la oferta elegida
     * @param int $usuarioId ID del administrador que genera la OC
     * @param string $observaciones Instrucciones o notas para el proveedor
     * @return OrdenCompra
     * @throws Exception Si la oferta no está elegida o ya tiene OC
     */
    public function ejecutar(int $ofertaId, int $usuarioId, string $observaciones): OrdenCompra
    {
        return DB::transaction(function () use ($ofertaId, $usuarioId, $observaciones) {
            
            // 1. VALIDAR OFERTA (Paso 1-2 CU-22)
            $oferta = $this->validarOferta($ofertaId);
            
            // 2. GENERAR ORDEN DE COMPRA (Paso 3-4 CU-22)
            $orden = $this->crearOrdenDesdeOferta($oferta, $usuarioId, $observaciones);
            
            // 3. GENERAR PDF (Paso 5 CU-22)
            $this->generarPdf($orden);
            
            // 4. MARCAR OFERTA COMO PROCESADA
            $oferta->marcarProcesada();
            
            // 5. ENVIAR WHATSAPP AL PROVEEDOR (Paso 6 CU-22 - Asíncrono)
            $this->enviarWhatsApp($orden);
            
            // 6. NOTIFICAR POR EMAIL AL ADMIN (Mailpit en desarrollo)
            $this->enviarEmail($orden, $usuarioId);
            
            // 7. AUDITORÍA (Kendall)
            Auditoria::registrar(
                accion: Auditoria::ACCION_GENERAR_ORDEN_COMPRA,
                tabla: 'ordenes_compra',
                registroId: $orden->id,
                motivo: $observaciones,
                detalles: "OC {$orden->numero_oc} generada. Proveedor: {$oferta->proveedor->razon_social}. Total: \${$orden->total_final}",
                usuarioId: $usuarioId
            );

            return $orden->fresh(['proveedor', 'oferta', 'detalles', 'estado']);
        });
    }

    /**
     * Valida que la oferta cumpla requisitos para generar OC
     * 
     * Excepciones:
     * - 10a: Oferta no encontrada
     * - 10b: Oferta ya tiene OC asociada (integridad 1:1)
     * - 10c: Oferta no está en estado "Elegida"
     */
    protected function validarOferta(int $ofertaId): OfertaCompra
    {
        $oferta = OfertaCompra::with(['solicitud', 'proveedor', 'detalles'])
            ->lockForUpdate()
            ->findOrFail($ofertaId);

        // Excepción 10b: Ya tiene OC
        if ($oferta->ordenesCompra()->exists()) {
            throw new Exception("Esta oferta ya tiene una Orden de Compra asociada (Integridad 1:1).");
        }

        // Excepción 10c: No está elegida
        if ($oferta->estado !== OfertaCompra::ESTADO_ELEGIDA) {
            throw new Exception("Solo se puede generar OC de ofertas con estado 'Elegida'. Estado actual: {$oferta->estado}");
        }

        return $oferta;
    }

    /**
     * Crea la orden de compra con sus detalles
     */
    protected function crearOrdenDesdeOferta(OfertaCompra $oferta, int $usuarioId, string $observaciones): OrdenCompra
    {
        // Crear cabecera
        $orden = OrdenCompra::create([
            'numero_oc'    => OrdenCompra::generarNumeroOC(),
            'proveedor_id' => $oferta->proveedor_id,
            'oferta_id'    => $oferta->id,
            'user_id'      => $usuarioId,
            'estado_id'    => EstadoOrdenCompra::idPorNombre(EstadoOrdenCompra::BORRADOR),
            'total_final'  => $oferta->precio_total,
            'fecha_emision'=> now(),
            'observaciones'=> $observaciones,
        ]);

        // Crear detalles desde la oferta
        $detallesOferta = $oferta->detalles;
        
        if ($detallesOferta->isEmpty()) {
            // Fallback: usar detalles de la solicitud
            $this->crearDetallesDesdeSolicitud($orden, $oferta);
        } else {
            // Caso ideal: usar detalles de la oferta
            foreach ($detallesOferta as $detalle) {
                $orden->detalles()->create([
                    'producto_id'      => $detalle->producto_id,
                    'cantidad_pedida'  => $detalle->cantidad_ofrecida,
                    'cantidad_recibida'=> 0,
                    'precio_unitario'  => $detalle->precio_unitario,
                ]);
            }
        }

        return $orden;
    }

    /**
     * Fallback: Crear detalles desde la solicitud original
     */
    protected function crearDetallesDesdeSolicitud(OrdenCompra $orden, OfertaCompra $oferta): void
    {
        $solicitud = $oferta->solicitud;
        
        if (!$solicitud || $solicitud->detalles->isEmpty()) {
            throw new Exception("La oferta no tiene detalles para generar la orden.");
        }
        
        $totalCantidad = $solicitud->detalles->sum('cantidad_sugerida');
        $precioPromedio = $totalCantidad > 0 ? ($oferta->precio_total / $totalCantidad) : 0;
        
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
     * Genera el PDF de la orden de compra (CU-22 Paso 5)
     * 
     * El PDF se almacena en storage/app/public/ordenes_compra/
     * y la ruta se guarda en el campo archivo_pdf
     */
    protected function generarPdf(OrdenCompra $orden): void
    {
        try {
            // Cargar relaciones necesarias para el PDF
            $orden->load(['proveedor', 'detalles.producto', 'usuario']);
            
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
            
        } catch (Exception $e) {
            Log::error("❌ Error generando PDF para OC {$orden->numero_oc}: " . $e->getMessage());
            // No lanzamos excepción para no abortar toda la transacción
            // El PDF puede regenerarse manualmente
        }
    }

    /**
     * Envía WhatsApp al proveedor con la OC (CU-22 Paso 6)
     * 
     * Usa Twilio para WhatsApp Business API
     * El envío es asíncrono via Job para no bloquear la respuesta
     */
    protected function enviarWhatsApp(OrdenCompra $orden): void
    {
        try {
            $proveedor = $orden->proveedor;
            
            // Validar que el proveedor tenga WhatsApp
            if (!$proveedor->whatsapp && !$proveedor->telefono) {
                Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin WhatsApp/teléfono. OC no enviada automáticamente.");
                $this->registrarAlertaInterna($orden, 'Proveedor sin teléfono registrado');
                return;
            }

            // Dispatch del Job (envío asíncrono con reintentos)
            EnviarOrdenCompraWhatsApp::dispatch($orden)
                ->onQueue('whatsapp');

            Log::info("📱 Job WhatsApp encolado para OC {$orden->numero_oc}");
            
        } catch (Exception $e) {
            Log::error("❌ Error encolando WhatsApp: " . $e->getMessage());
            $orden->marcarEnvioFallido();
            $this->registrarAlertaInterna($orden, 'Error al enviar WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Envía emails de la OC (CU-22 Paso 7)
     * 
     * - Al proveedor: con PDF adjunto para confirmar la orden
     * - Al administrador: notificación de la OC generada
     * 
     * En desarrollo usa Mailpit (localhost:1025)
     */
    protected function enviarEmail(OrdenCompra $orden, int $usuarioId): void
    {
        // 1. Email al PROVEEDOR (con PDF adjunto)
        try {
            $proveedor = $orden->proveedor;
            
            if ($proveedor && $proveedor->email) {
                $proveedor->notify(new OrdenCompraProveedor($orden));
                Log::info("📧 Email de OC enviado al proveedor {$proveedor->email}");
            } else {
                Log::warning("⚠️ Proveedor {$proveedor->razon_social} sin email. OC no enviada por correo.");
            }
            
        } catch (Exception $e) {
            Log::warning("⚠️ No se pudo enviar email al proveedor: " . $e->getMessage());
        }

        // 2. Email al ADMINISTRADOR (notificación interna)
        try {
            $usuario = User::find($usuarioId);
            
            if ($usuario && $usuario->email) {
                $usuario->notify(new OrdenCompraGenerada($orden));
                Log::info("📧 Email de notificación enviado al admin {$usuario->email}");
            }
            
        } catch (Exception $e) {
            Log::warning("⚠️ No se pudo enviar email al administrador: " . $e->getMessage());
        }
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