<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Días de gracia global
        Configuracion::set(
            'dias_gracia_global',
            15,
            'Número de días de gracia para el cálculo de saldo vencido global.'
        );

        // 2. Bloqueo automático (Crucial para CU-09)
        Configuracion::set(
            'bloqueo_automatico_cc',
            'true',
            'Define si el sistema bloquea automáticamente las CC al incumplir. (true/false)'
        );

        // 3. Límite de crédito global
        // CAMBIO AQUÍ: Usamos 'limite_credito_global' para coincidir con el Modelo
        Configuracion::set(
            'limite_credito_global',
            100000.00,
            'Límite de crédito base para clientes sin uno específico.'
        );

        // 4. WhatsApp Admin (Muy útil para las notificaciones)
        Configuracion::set(
            'whatsapp_admin_notificaciones',
            '03754532041',
            'Número de WhatsApp del administrador para alertas del sistema.'
        );
    }
}
