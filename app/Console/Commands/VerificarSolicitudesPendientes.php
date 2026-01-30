<?php

namespace App\Console\Commands;

use App\Models\SolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Jobs\EnviarSolicitudCotizacionEmail;
use App\Jobs\EnviarSolicitudCotizacionWhatsApp;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comando: Verificar Solicitudes Pendientes (Requerimiento Profesor)
 * 
 * Se ejecuta cada 6 horas para:
 * 1. Verificar si solicitudes están vencidas
 * 2. Enviar recordatorios a proveedores que no respondieron
 * 3. Cerrar solicitudes automáticamente si pasó el tiempo
 * 4. Notificar al administrador de solicitudes cerradas
 */
class VerificarSolicitudesPendientes extends Command
{
    protected $signature = 'compras:verificar-solicitudes';
    protected $description = 'Verifica solicitudes pendientes, envía recordatorios y cierra vencidas';

    public function handle()
    {
        $this->info('🔍 Verificando solicitudes de cotización pendientes...');

        // 1. Obtener solicitudes abiertas/enviadas
        $solicitudes = SolicitudCotizacion::with(['estado', 'cotizacionesProveedores.proveedor'])
            ->whereHas('estado', fn($q) => $q->whereIn('nombre', ['Enviada', 'Abierta']))
            ->where('fecha_vencimiento', '<=', Carbon::now()->addDays(2)) // Por vencer o vencidas
            ->get();

        if ($solicitudes->isEmpty()) {
            $this->info('✅ No hay solicitudes que requieran atención');
            return 0;
        }

        $recordatorios = 0;
        $cerradas = 0;

        foreach ($solicitudes as $solicitud) {
            $horasRestantes = Carbon::parse($solicitud->fecha_vencimiento)->diffInHours(now(), false);
            
            // Ya venció
            if ($horasRestantes <= 0) {
                $this->cerrarSolicitudVencida($solicitud);
                $cerradas++;
                continue;
            }

            // Falta poco (48h o menos) - enviar recordatorio
            if ($horasRestantes <= 48) {
                $enviados = $this->enviarRecordatorios($solicitud);
                $recordatorios += $enviados;
            }
        }

        $this->info("✅ Proceso completado:");
        $this->info("   📧 Recordatorios enviados: {$recordatorios}");
        $this->info("   🔒 Solicitudes cerradas: {$cerradas}");

        Log::info("Verificación de solicitudes completada", [
            'recordatorios' => $recordatorios,
            'cerradas' => $cerradas,
        ]);

        return 0;
    }

    /**
     * Enviar recordatorios a proveedores que no han respondido
     */
    protected function enviarRecordatorios(SolicitudCotizacion $solicitud): int
    {
        $enviados = 0;
        $horasRestantes = Carbon::parse($solicitud->fecha_vencimiento)->diffInHours(now());

        foreach ($solicitud->cotizacionesProveedores as $cotizacion) {
            // Solo recordar si:
            // 1. Fue enviado
            // 2. No ha respondido
            // 3. No se le ha enviado recordatorio en las últimas 12 horas
            if ($cotizacion->fecha_envio && 
                !$cotizacion->fecha_respuesta &&
                (!$cotizacion->fecha_ultimo_recordatorio || 
                 $cotizacion->fecha_ultimo_recordatorio->diffInHours(now()) >= 12)) {
                
                $this->info("📧 Enviando recordatorio a {$cotizacion->proveedor->razon_social}");
                
                // Enviar por ambos canales
                if ($cotizacion->proveedor->email) {
                    EnviarSolicitudCotizacionEmail::dispatch($cotizacion, esRecordatorio: true);
                }
                if ($cotizacion->proveedor->telefono) {
                    EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion, esRecordatorio: true);
                }

                $cotizacion->update([
                    'fecha_ultimo_recordatorio' => now(),
                    'cantidad_recordatorios' => ($cotizacion->cantidad_recordatorios ?? 0) + 1,
                ]);

                $enviados++;
            }
        }

        return $enviados;
    }

    /**
     * Cerrar solicitud vencida y generar reporte
     */
    protected function cerrarSolicitudVencida(SolicitudCotizacion $solicitud)
    {
        $this->warn("🔒 Cerrando solicitud vencida: {$solicitud->codigo_solicitud}");

        $estadoCompletada = \App\Models\EstadoSolicitud::where('nombre', 'Completada')->first();
        
        $solicitud->update([
            'estado_id' => $estadoCompletada->id,
        ]);

        // Contar respuestas
        $total = $solicitud->cotizacionesProveedores->count();
        $respondieron = $solicitud->cotizacionesProveedores->whereNotNull('fecha_respuesta')->count();
        $noRespondieron = $total - $respondieron;

        Log::info("Solicitud cerrada por vencimiento", [
            'solicitud' => $solicitud->codigo_solicitud,
            'total_proveedores' => $total,
            'respondieron' => $respondieron,
            'no_respondieron' => $noRespondieron,
        ]);

        // TODO: Enviar notificación al administrador con resumen
        // Mail::to(config('mail.admin'))->send(new SolicitudVencidaMail($solicitud));
    }
}
