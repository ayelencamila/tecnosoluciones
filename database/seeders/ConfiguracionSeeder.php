<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        // --- CONFIGURACIONES DE CUENTA CORRIENTE (CU-09) ---
        
        Configuracion::set(
            'dias_gracia_global',
            30,
            'Días de gracia por defecto para pagos de cuenta corriente (si el cliente no tiene días específicos)'
        );

        Configuracion::set(
            'limite_credito_global',
            10000.00,
            'Límite de crédito por defecto en ARS (si el cliente no tiene límite específico)'
        );

        Configuracion::set(
            'politicaAutoBlock',
            'true',
            'Bloqueo automático de cuenta corriente cuando hay incumplimiento (true/false)'
        );

        Configuracion::set(
            'whatsapp_admin_notificaciones',
            '+5491112345678',
            'Número de WhatsApp del administrador para recibir notificaciones de incumplimientos'
        );

        // --- CONFIGURACIONES DE VENTAS ---
        
        Configuracion::set(
            'dias_maximos_anulacion_venta',
            30,
            'Días máximos permitidos para anular una venta después de registrada'
        );

        Configuracion::set(
            'permitir_venta_sin_stock',
            'false',
            'Permite registrar ventas aunque el producto no tenga stock disponible (true/false)'
        );

        // --- CONFIGURACIONES DE STOCK ---
        
        Configuracion::set(
            'stock_minimo_global',
            5,
            'Stock mínimo por defecto para productos (alerta cuando stock < este valor)'
        );

        Configuracion::set(
            'alerta_stock_bajo',
            'true',
            'Activar alertas automáticas cuando el stock esté bajo (true/false)'
        );

        // --- CONFIGURACIONES GENERALES ---
        
        Configuracion::set(
            'nombre_empresa',
            'TecnoSoluciones',
            'Nombre legal de la empresa para comprobantes'
        );

        Configuracion::set(
            'cuit_empresa',
            '30-12345678-9',
            'CUIT de la empresa'
        );

        Configuracion::set(
            'email_contacto',
            'contacto@tecnosoluciones.com',
            'Email de contacto de la empresa'
        );

        Configuracion::set(
            'direccion_empresa',
            'Av. Principal 123, CABA',
            'Dirección física de la empresa'
        );

        $this->command->info('✅ Configuraciones del sistema cargadas correctamente.');
    }
}
