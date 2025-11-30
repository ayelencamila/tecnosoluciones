<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        // =======================================================
        // 1. GENERALES (Empresa y Sistema)
        // =======================================================
        Configuracion::set(
            'nombre_empresa',
            'TecnoSoluciones',
            'Nombre legal de la empresa para comprobantes y encabezados.'
        );

        Configuracion::set(
            'cuit_empresa',
            '30-12345678-9',
            'CUIT de la empresa para reportes internos.'
        );

        Configuracion::set(
            'email_contacto',
            'contacto@tecnosoluciones.com',
            'Email de contacto visible en el sistema.'
        );

        Configuracion::set(
            'direccion_empresa',
            'Av. Principal 123, CABA',
            'Dirección física de la empresa.'
        );

        // =======================================================
        // 2. MÓDULO CUENTA CORRIENTE (CU-09)
        // =======================================================
        Configuracion::set(
            'dias_gracia_global',
            30,
            'Días de gracia por defecto para pagos de cuenta corriente (si el cliente no tiene específico).'
        );

        Configuracion::set(
            'limite_credito_global',
            100000.00, // Ajustado a un valor más realista
            'Límite de crédito por defecto en ARS para nuevos clientes mayoristas.'
        );

        Configuracion::set(
            'politicaAutoBlock',
            'true',
            'Habilitar bloqueo automático de CC ante incumplimientos (true/false).'
        );

        Configuracion::set(
            'whatsapp_admin_notificaciones',
            '+5491112345678',
            'Número de WhatsApp del administrador para recibir alertas críticas de CC.'
        );

        // =======================================================
        // 3. MÓDULO VENTAS
        // =======================================================
        Configuracion::set(
            'dias_maximos_anulacion_venta',
            7, // Ajustado: 30 días suele ser mucho para anulación directa
            'Días máximos permitidos para anular una venta después de registrada.'
        );

        Configuracion::set(
            'permitir_venta_sin_stock',
            'false',
            'Permite registrar ventas aunque el sistema indique stock cero (true/false).'
        );

        // =======================================================
        // 4. MÓDULO STOCK
        // =======================================================
        Configuracion::set(
            'stock_minimo_global',
            5,
            'Punto de pedido por defecto. Alerta cuando stock < este valor.'
        );

        Configuracion::set(
            'alerta_stock_bajo',
            'true',
            'Activar notificaciones visuales automáticas cuando el stock esté bajo.'
        );

        // =======================================================
        // 5. MÓDULO REPARACIONES (Soporte para CU-14 y RF15)
        // =======================================================
        Configuracion::set(
            'reparacion_sla_dias_estandar',
            3,
            'Días hábiles estimados por defecto para una reparación estándar (SLA).'
        );

        Configuracion::set(
            'reparacion_habilitar_bonificacion',
            'true',
            'Habilitar política de descuentos automáticos por demoras en reparaciones (true/false).'
        );

        Configuracion::set(
            'reparacion_bonificacion_diaria_porc',
            0.5,
            'Porcentaje de descuento diario sobre la mano de obra por cada día de demora fuera de SLA.'
        );

        Configuracion::set(
            'reparacion_tope_bonificacion_porc',
            20,
            'Tope máximo de bonificación acumulada (porcentaje) por demora.'
        );

        // =======================================================
        // 6. MÓDULO COMUNICACIÓN / WHATSAPP (Soporte para CU-30)
        // =======================================================
        Configuracion::set(
            'whatsapp_activo',
            'true',
            'Interruptor general para el envío de mensajes automáticos por WhatsApp.'
        );

        Configuracion::set(
            'whatsapp_horario_inicio',
            '09:00',
            'Hora de inicio permitida para el envío de notificaciones automáticas.'
        );

        Configuracion::set(
            'whatsapp_horario_fin',
            '20:00',
            'Hora de fin para el envío de notificaciones automáticas (evita mensajes nocturnos).'
        );
        
        Configuracion::set(
            'whatsapp_reintentos_maximos',
            3,
            'Cantidad de intentos de reenvío ante falla de comunicación.'
        );

        Configuracion::set(
            'whatsapp_plantilla_bloqueo',
            "⚠️ *CUENTA BLOQUEADA*\nHola [nombre_cliente], su cuenta ha sido suspendida temporalmente.\nMotivo: _[motivo]_.\nPor favor regularice su situación.",
            'Plantilla para notificación de bloqueo automático.'
        );

        Configuracion::set(
            'whatsapp_plantilla_revision',
            "⚠️ *CUENTA EN REVISIÓN*\nHola [nombre_cliente], su estado de crédito está bajo análisis administrativo.\nMotivo: _[motivo]_.",
            'Plantilla para notificación de revisión de cuenta.'
        );

        Configuracion::set(
            'whatsapp_plantilla_recordatorio',
            "🔔 *RECORDATORIO DE PAGO*\nHola [nombre_cliente], le recordamos que presenta saldos pendientes.\nEstado: _[motivo]_.\nEvite el bloqueo de su cuenta.",
            'Plantilla para recordatorio de deuda.'
        );


        $this->command->info('✅ Configuraciones globales del sistema (Todos los módulos) cargadas correctamente.');
    }
}
