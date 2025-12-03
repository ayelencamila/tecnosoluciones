<?php

namespace App\Services\Reparaciones;

use App\Models\AlertaReparacion;
use App\Models\BonificacionReparacion;
use App\Models\Reparacion;
use App\Models\Configuracion;
use App\Jobs\NotificarBonificacionCliente;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión de bonificaciones por demora en reparaciones (CU-14)
 * Principios GRASP: Expert, Service Layer, Low Coupling
 */
class BonificacionService
{
    /**
     * Genera bonificación automática basándose en la respuesta del técnico
     * 
     * @param int $alertaID ID de la alerta resuelta por el técnico
     * @return BonificacionReparacion
     * @throws \Exception Si la alerta no tiene respuesta del técnico
     */
    public function generarBonificacionAutomatica(int $alertaID): BonificacionReparacion
    {
        $alerta = AlertaReparacion::with(['reparacion', 'tecnico'])->findOrFail($alertaID);
        
        // Verificar que el técnico haya respondido
        if (!$alerta->respuesta_tecnico) {
            throw new \Exception("La alerta #{$alertaID} no tiene respuesta del técnico");
        }

        $reparacion = $alerta->reparacion;
        $respuesta = $alerta->respuesta_tecnico;
        
        // Calcular porcentaje según días excedidos y configuración
        $porcentajeSugerido = $this->calcularPorcentajeSugerido($alerta->dias_excedidos);
        
        // Obtener monto original (costo de la reparación)
        $montoOriginal = $reparacion->total_final ?? $reparacion->costo_mano_obra ?? 0;
        
        // Calcular monto de descuento
        $montoDescuento = $montoOriginal * ($porcentajeSugerido / 100);
        
        // Crear bonificación en estado pendiente
        $bonificacion = BonificacionReparacion::create([
            'reparacionID' => $reparacion->reparacionID,
            'porcentaje_sugerido' => $porcentajeSugerido,
            'porcentaje_aprobado' => null, // Pendiente de aprobación
            'monto_original' => $montoOriginal,
            'monto_bonificado' => $montoDescuento,
            'dias_excedidos' => $alerta->dias_excedidos,
            'justificacion_tecnico' => $respuesta['observaciones'] ?? '',
            'motivoDemoraID' => $respuesta['motivo_id'] ?? null,
            'estado' => 'pendiente',
        ]);
        
        Log::info('Bonificación generada automáticamente', [
            'bonificacion_id' => $bonificacion->bonificacionID,
            'reparacion_id' => $reparacion->reparacionID,
            'porcentaje_sugerido' => $porcentajeSugerido,
            'dias_excedidos' => $alerta->dias_excedidos,
            'monto_original' => $montoOriginal,
            'monto_descuento' => $montoDescuento,
        ]);
        
        return $bonificacion;
    }
    
    /**
     * Calcula porcentaje de bonificación según días excedidos
     * Lee configuración parametrizable del admin
     * 
     * @param int $diasExcedidos
     * @return float Porcentaje de bonificación
     */
    public function calcularPorcentajeSugerido(int $diasExcedidos): float
    {
        if ($diasExcedidos <= 0) {
            return 0;
        }
        
        // Leer configuración parametrizable
        if ($diasExcedidos <= 3) {
            $porcentaje = (float) Configuracion::get('bonificacion_1_3_dias', 10);
        } elseif ($diasExcedidos <= 7) {
            $porcentaje = (float) Configuracion::get('bonificacion_4_7_dias', 15);
        } else {
            $porcentaje = (float) Configuracion::get('bonificacion_mas_7_dias', 20);
        }
        
        // Aplicar tope máximo
        $topeMaximo = (float) Configuracion::get('bonificacion_tope_maximo', 50);
        
        return min($porcentaje, $topeMaximo);
    }
    
    /**
     * Admin aprueba la bonificación con el porcentaje sugerido
     * 
     * @param int $bonificacionID
     * @param int $adminID
     * @param string|null $observaciones
     * @return BonificacionReparacion
     */
    public function aprobarBonificacion(int $bonificacionID, int $adminID, ?string $observaciones = null): BonificacionReparacion
    {
        $bonificacion = BonificacionReparacion::with('reparacion.cliente')->findOrFail($bonificacionID);
        
        // Aprobar con el porcentaje sugerido
        $bonificacion->aprobar($adminID, $observaciones);
        
        // Encolar notificación WhatsApp al cliente
        NotificarBonificacionCliente::dispatch($bonificacionID);
        
        Log::info('Bonificación aprobada por admin', [
            'bonificacion_id' => $bonificacionID,
            'admin_id' => $adminID,
            'porcentaje' => $bonificacion->porcentaje_aprobado,
            'cliente_id' => $bonificacion->reparacion->clienteID,
        ]);
        
        return $bonificacion->fresh();
    }
    
    /**
     * Admin ajusta el porcentaje y aprueba
     * 
     * @param int $bonificacionID
     * @param int $adminID
     * @param float $porcentajeAjustado
     * @param string|null $observaciones
     * @return BonificacionReparacion
     */
    public function ajustarYAprobarBonificacion(
        int $bonificacionID,
        int $adminID,
        float $porcentajeAjustado,
        ?string $observaciones = null
    ): BonificacionReparacion {
        $bonificacion = BonificacionReparacion::with('reparacion.cliente')->findOrFail($bonificacionID);
        
        // Validar que el porcentaje esté dentro del tope
        $topeMaximo = (float) Configuracion::get('bonificacion_tope_maximo', 50);
        if ($porcentajeAjustado > $topeMaximo) {
            throw new \Exception("El porcentaje no puede exceder el tope máximo de {$topeMaximo}%");
        }
        
        // Aprobar con porcentaje personalizado
        $bonificacion->aprobarConPorcentaje($adminID, $porcentajeAjustado, $observaciones);
        
        // Encolar notificación WhatsApp al cliente
        NotificarBonificacionCliente::dispatch($bonificacionID);
        
        Log::info('Bonificación ajustada y aprobada por admin', [
            'bonificacion_id' => $bonificacionID,
            'admin_id' => $adminID,
            'porcentaje_sugerido' => $bonificacion->porcentaje_sugerido,
            'porcentaje_aprobado' => $porcentajeAjustado,
            'cliente_id' => $bonificacion->reparacion->clienteID,
        ]);
        
        return $bonificacion->fresh();
    }
    
    /**
     * Admin rechaza la bonificación
     * 
     * @param int $bonificacionID
     * @param int $adminID
     * @param string $motivo
     * @return BonificacionReparacion
     */
    public function rechazarBonificacion(int $bonificacionID, int $adminID, string $motivo): BonificacionReparacion
    {
        $bonificacion = BonificacionReparacion::findOrFail($bonificacionID);
        
        $bonificacion->rechazar($adminID, $motivo);
        
        Log::info('Bonificación rechazada por admin', [
            'bonificacion_id' => $bonificacionID,
            'admin_id' => $adminID,
            'motivo' => $motivo,
        ]);
        
        return $bonificacion->fresh();
    }
    
    /**
     * Registrar decisión del cliente (aceptar o cancelar)
     * 
     * @param int $bonificacionID
     * @param string $decision 'aceptar' o 'cancelar'
     * @param string|null $observaciones
     * @return BonificacionReparacion
     */
    public function registrarDecisionCliente(
        int $bonificacionID,
        string $decision,
        ?string $observaciones = null
    ): BonificacionReparacion {
        $bonificacion = BonificacionReparacion::with('reparacion')->findOrFail($bonificacionID);
        
        // Validar decisión
        if (!in_array($decision, ['aceptar', 'cancelar'])) {
            throw new \Exception("Decisión inválida. Debe ser 'aceptar' o 'cancelar'");
        }
        
        $bonificacion->registrarDecisionCliente($decision, $observaciones);
        
        Log::info('Cliente registró decisión sobre bonificación', [
            'bonificacion_id' => $bonificacionID,
            'decision' => $decision,
            'reparacion_id' => $bonificacion->reparacionID,
            'cliente_id' => $bonificacion->reparacion->clienteID,
        ]);
        
        return $bonificacion->fresh();
    }
    
    /**
     * Obtener bonificaciones pendientes de aprobación
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerBonificacionesPendientes()
    {
        return BonificacionReparacion::with([
            'reparacion.cliente',
            'reparacion.marca',
            'reparacion.modelo',
            'motivoDemora'
        ])
        ->pendientes()
        ->orderBy('created_at', 'desc')
        ->get();
    }
    
    /**
     * Obtener bonificaciones aprobadas pendientes de decisión del cliente
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerBonificacionesPendientesDecision()
    {
        return BonificacionReparacion::with([
            'reparacion.cliente',
            'reparacion.marca',
            'reparacion.modelo'
        ])
        ->aprobadas()
        ->whereNull('decision_cliente')
        ->orderBy('fecha_aprobacion', 'desc')
        ->get();
    }
}
