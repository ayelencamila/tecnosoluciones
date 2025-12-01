<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. GENERALES
        Configuracion::set('nombre_empresa', 'TecnoSoluciones', 'Nombre legal de la empresa.');
        Configuracion::set('cuit_empresa', '30-12345678-9', 'CUIT de la empresa.');
        Configuracion::set('email_contacto', 'contacto@tecnosoluciones.com', 'Email visible.');
        Configuracion::set('direccion_empresa', 'Av. Principal 123, CABA', 'Dirección física.');

        // 2. CUENTA CORRIENTE
        Configuracion::set('dias_gracia_global', 30, 'Días de gracia por defecto.');
        Configuracion::set('limite_credito_global', 100000.00, 'Límite de crédito por defecto (ARS).');
        Configuracion::set('politicaAutoBlock', 'true', 'Bloqueo automático ante incumplimientos (true/false).');
        Configuracion::set('whatsapp_admin_notificaciones', '+5491112345678', 'WhatsApp del admin para alertas críticas.');

        // 3. VENTAS
        Configuracion::set('dias_maximos_anulacion_venta', 7, 'Días máximos para anular venta.');
        Configuracion::set('permitir_venta_sin_stock', 'false', 'Permitir ventas con stock cero.');

        // 4. STOCK
        Configuracion::set('stock_minimo_global', 5, 'Alerta stock bajo por defecto.');
        Configuracion::set('alerta_stock_bajo', 'true', 'Activar alertas visuales de stock.');

        // 5. REPARACIONES
        Configuracion::set('reparacion_sla_dias_estandar', 3, 'SLA estándar (días).');
        Configuracion::set('reparacion_habilitar_bonificacion', 'true', 'Habilitar descuentos por demora.');
        Configuracion::set('reparacion_bonificacion_diaria_porc', 0.5, '% Descuento diario por demora.');
        Configuracion::set('reparacion_tope_bonificacion_porc', 20, 'Tope máximo de bonificación (%).');

        // 6. COMUNICACIÓN
        Configuracion::set('whatsapp_activo', 'true', 'Activar envío de WhatsApp.');
        Configuracion::set('whatsapp_horario_inicio', '09:00', 'Hora inicio notificaciones.');
        Configuracion::set('whatsapp_horario_fin', '20:00', 'Hora fin notificaciones.');
        Configuracion::set('whatsapp_reintentos_maximos', 3, 'Intentos de reenvío.');

        // 7. PLANTILLAS WHATSAPP (CU-30)
        // Variables disponibles: [nombre_cliente], [motivo]
        Configuracion::set(
            'whatsapp_plantilla_bloqueo', 
            'Hola [nombre_cliente], su cuenta ha sido BLOQUEADA por: [motivo]. Por favor regularice su situación.',
            'Plantilla para bloqueo. Variables: [nombre_cliente], [motivo]'
        );
        Configuracion::set(
            'whatsapp_plantilla_revision', 
            'Hola [nombre_cliente], su cuenta está en REVISIÓN por: [motivo].',
            'Plantilla para revisión. Variables: [nombre_cliente], [motivo]'
        );
        Configuracion::set(
            'whatsapp_plantilla_recordatorio', 
            'Hola [nombre_cliente], recordatorio de saldo pendiente: [motivo].',
            'Plantilla para recordatorio. Variables: [nombre_cliente], [motivo]'
        );

        $this->command->info('✅ Configuraciones globales cargadas correctamente.');
    }
}
