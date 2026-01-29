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

        // 4. STOCK Y COMPRAS (CU-20)
        Configuracion::set('stock_minimo_global', 5, 'Alerta stock bajo por defecto.');
        Configuracion::set('alerta_stock_bajo', 'true', 'Activar alertas visuales de stock.');
        Configuracion::set('solicitud_cotizacion_dias_vencimiento', 7, '[Compras] Días de vencimiento por defecto para solicitudes de cotización.');
        Configuracion::set('solicitud_cotizacion_dias_recordatorio', 2, '[Compras] Días desde el envío para enviar recordatorio a proveedores.');
        Configuracion::set('solicitud_cotizacion_max_recordatorios', 3, '[Compras] Cantidad máxima de recordatorios a enviar por solicitud.');

        // 5. REPARACIONES
        // NOTA: Configuraciones de reparaciones movidas a ConfiguracionReparacionesSeeder (CU-31)
        Configuracion::set('reparacion_bonificacion_diaria_porc', 0.5, '% Descuento diario por demora.');
        Configuracion::set('reparacion_tope_bonificacion_porc', 20, 'Tope máximo de bonificación (%).');

        // 6. COMUNICACIÓN (Parámetros básicos - Las plantillas están en módulo separado CU-30)
        Configuracion::set('whatsapp_activo', 'true', 'Activar envío de WhatsApp.');
        Configuracion::set('whatsapp_horario_inicio', '09:00', 'Hora inicio notificaciones (global, puede ser sobrescrito por plantilla).');
        Configuracion::set('whatsapp_horario_fin', '20:00', 'Hora fin notificaciones (global, puede ser sobrescrito por plantilla).');
        Configuracion::set('whatsapp_reintentos_maximos', 3, 'Intentos de reenvío.');

        $this->command->info('✅ Configuraciones globales cargadas correctamente.');
        $this->command->info('ℹ️  Las plantillas de WhatsApp se gestionan desde el módulo "Plantillas WhatsApp" (CU-30)');
    }
}
