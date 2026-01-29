<?php

namespace App\Services\Reparaciones;

use App\Models\Reparacion;
use App\Models\AlertaReparacion;
use App\Models\TipoAlertaReparacion;
use App\Models\Configuracion;
use App\Notifications\ReparacionDemoradaNotification;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para monitorear y gestionar el SLA de reparaciones (CU-14)
 * Principio: Information Expert + Service Layer (SRP)
 */
class MonitoreoSLAReparacionService
{
    /**
     * Verifica todas las reparaciones activas y genera alertas si exceden SLA
     * 
     * @return array Estadísticas del monitoreo
     */
    public function verificarYGenerarAlertas(): array
    {
        // SOLO monitorear reparaciones en estados "Recibido" o "Diagnóstico"
        // según especificación CU-14
        $reparaciones = Reparacion::activas()
            ->enEstadosMonitoreables()
            ->sinEntregar()
            ->with(['tecnico', 'estado'])
            ->get();

        $stats = [
            'total_verificadas' => $reparaciones->count(),
            'alertas_generadas' => 0,
            'exceden_sla' => 0,
            'incumplen_sla' => 0,
        ];

        foreach ($reparaciones as $reparacion) {
            $resultado = $this->verificarReparacion($reparacion);
            
            if ($resultado['excede']) {
                $stats['exceden_sla']++;
                
                if ($resultado['alerta_generada']) {
                    $stats['alertas_generadas']++;
                }
            }

            if ($resultado['incumple']) {
                $stats['incumplen_sla']++;
            }
        }

        Log::info('Monitoreo SLA completado', $stats);

        return $stats;
    }

    /**
     * Verifica una reparación específica y genera alerta si corresponde
     * 
     * @param Reparacion $reparacion
     * @return array Resultado de la verificación
     */
    public function verificarReparacion(Reparacion $reparacion): array
    {
        // Calcular estado del SLA
        $estadoSLA = $reparacion->excedeOIncumpleSLA();

        $resultado = [
            'excede' => $estadoSLA['excede'],
            'incumple' => $estadoSLA['incumple'],
            'dias_efectivos' => $estadoSLA['dias_efectivos'],
            'dias_excedidos' => $estadoSLA['dias_excedidos'],
            'alerta_generada' => false,
        ];

        // Si no excede SLA, no hacer nada
        if (!$estadoSLA['excede']) {
            return $resultado;
        }

        // Marcar reparación como demorada en BD
        $reparacion->marcarComoDemorada();

        // Obtener tipo de alerta SLA Excedido (ID = 1)
        $tipoAlertaId = TipoAlertaReparacion::SLA_EXCEDIDO;

        // Verificar si ya existe alerta del mismo tipo y no leída
        $alertaExistente = AlertaReparacion::where('reparacionID', $reparacion->reparacionID)
            ->where('tecnicoID', $reparacion->tecnico_id)
            ->where('tipo_alerta_id', $tipoAlertaId)
            ->where('leida', false)
            ->exists();

        if ($alertaExistente) {
            return $resultado;
        }

        // Crear nueva alerta
        $alerta = AlertaReparacion::create([
            'reparacionID' => $reparacion->reparacionID,
            'tecnicoID' => $reparacion->tecnico_id,
            'tipo_alerta_id' => $tipoAlertaId,
            'dias_excedidos' => $estadoSLA['dias_excedidos'],
            'dias_efectivos' => $estadoSLA['dias_efectivos'],
            'sla_vigente' => $estadoSLA['sla_vigente'],
            'leida' => false,
        ]);

        $resultado['alerta_generada'] = true;

        // CU-14 Paso 6: Notificar al técnico SOLO por campanita (NO WhatsApp)
        $this->notificarTecnicoCampanita($reparacion, $estadoSLA['dias_excedidos'], $alerta->alertaReparacionID);

        Log::info("Alerta de SLA excedido generada y notificación campanita enviada al técnico", [
            'reparacion_id' => $reparacion->reparacionID,
            'codigo' => $reparacion->codigo_reparacion,
            'tecnico_id' => $reparacion->tecnico_id,
            'dias_excedidos' => $estadoSLA['dias_excedidos'],
            'alerta_id' => $alerta->alertaReparacionID,
        ]);

        return $resultado;
    }

    /**
     * Notifica al técnico por campanita (notificación interna)
     */
    protected function notificarTecnicoCampanita(Reparacion $reparacion, int $diasExcedidos, int $alertaId): void
    {
        $tecnico = $reparacion->tecnico;
        
        if ($tecnico) {
            $tecnico->notify(new ReparacionDemoradaNotification($reparacion, $diasExcedidos, 'tecnico', $alertaId));
            Log::info("Notificación campanita enviada a técnico", [
                'tecnico_id' => $tecnico->id,
                'reparacion_id' => $reparacion->reparacionID,
                'alerta_id' => $alertaId,
            ]);
        }
    }

    /**
     * Obtiene reparaciones que exceden SLA agrupadas por técnico
     * 
     * @return array
     */
    public function getReparacionesExcedidasPorTecnico(): array
    {
        $reparaciones = Reparacion::conSLAExcedido()
            ->activas()
            ->enEstadosMonitoreables()
            ->sinEntregar()
            ->with(['tecnico', 'cliente', 'estado'])
            ->get();

        $agrupadas = [];

        foreach ($reparaciones as $reparacion) {
            $tecnicoID = $reparacion->tecnico_id;
            
            if (!isset($agrupadas[$tecnicoID])) {
                $agrupadas[$tecnicoID] = [
                    'tecnico' => $reparacion->tecnico,
                    'total_excedidas' => 0,
                    'reparaciones' => [],
                ];
            }

            $agrupadas[$tecnicoID]['total_excedidas']++;
            $agrupadas[$tecnicoID]['reparaciones'][] = [
                'reparacion' => $reparacion,
                'sla_info' => $reparacion->excedeOIncumpleSLA(),
            ];
        }

        return $agrupadas;
    }

    /**
     * Calcula porcentaje de bonificación según días de exceso
     * 
     * @param int $diasExcedidos
     * @return int Porcentaje de bonificación
     */
    public function calcularPorcentajeBonificacion(int $diasExcedidos): int
    {
        if ($diasExcedidos <= 0) {
            return 0;
        }

        if ($diasExcedidos <= 3) {
            return (int) Configuracion::get('bonificacion_1_3_dias', 10);
        }

        if ($diasExcedidos <= 7) {
            return (int) Configuracion::get('bonificacion_4_7_dias', 20);
        }

        return (int) Configuracion::get('bonificacion_mas_7_dias', 30);
    }

    /**
     * Aplica tope máximo a bonificación
     * 
     * @param int $porcentajeSugerido
     * @return int Porcentaje ajustado al tope
     */
    public function aplicarTopeBonificacion(int $porcentajeSugerido): int
    {
        $topeMaximo = (int) Configuracion::get('bonificacion_tope_maximo', 50);
        return min($porcentajeSugerido, $topeMaximo);
    }
}
