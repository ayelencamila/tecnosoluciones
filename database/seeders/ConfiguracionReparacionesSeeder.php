<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfiguracionReparacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            // ===== SLA =====
            [
                'clave' => 'sla_reparaciones_default',
                'valor' => '7',
                'descripcion' => '[Reparaciones - SLA] Días de SLA por defecto para todas las reparaciones',
            ],
            
            // ===== BONIFICACIONES =====
            [
                'clave' => 'bonificacion_demora_habilitada',
                'valor' => 'true',
                'descripcion' => '[Reparaciones - Bonificaciones] Habilitar sistema de bonificaciones por demora en reparaciones',
            ],
            [
                'clave' => 'bonificacion_1_3_dias',
                'valor' => '10',
                'descripcion' => '[Reparaciones - Bonificaciones] Porcentaje de bonificación para demoras de 1-3 días sobre SLA',
            ],
            [
                'clave' => 'bonificacion_4_7_dias',
                'valor' => '20',
                'descripcion' => '[Reparaciones - Bonificaciones] Porcentaje de bonificación para demoras de 4-7 días sobre SLA',
            ],
            [
                'clave' => 'bonificacion_mas_7_dias',
                'valor' => '30',
                'descripcion' => '[Reparaciones - Bonificaciones] Porcentaje de bonificación para demoras de más de 7 días sobre SLA',
            ],
            [
                'clave' => 'bonificacion_tope_maximo',
                'valor' => '50',
                'descripcion' => '[Reparaciones - Bonificaciones] Tope máximo de bonificación aplicable (% sobre mano de obra)',
            ],
            
            // ===== ESTADOS QUE PAUSAN SLA =====
            [
                'clave' => 'estados_pausa_sla',
                'valor' => 'En espera de aprobación,En espera de repuesto,Listo para retiro',
                'descripcion' => '[Reparaciones - SLA] Nombres de estados que pausan el conteo de días efectivos para SLA (separados por coma). IMPORTANTE: Estos nombres deben coincidir exactamente con los de la tabla estados_reparacion',
            ],
            
            // ===== PLANTILLAS WHATSAPP =====
            [
                'clave' => 'whatsapp_template_reparacion_demorada',
                'valor' => "Estimado/a {cliente}, su reparación #{codigo} presenta demora.\n\nDías excedidos: {dias}\nMotivo: {motivo}\n\n{bonificacion}¿Desea continuar con la reparación?\n\nPor favor responda Sí o No.",
                'descripcion' => '[Reparaciones - Comunicación] Plantilla WhatsApp para notificar reparación demorada. Variables: {cliente}, {codigo}, {dias}, {motivo}, {bonificacion}',
            ],
            [
                'clave' => 'whatsapp_template_bonificacion',
                'valor' => 'Se aplicará una bonificación del {porcentaje}% sobre la mano de obra.' . "\n" .
                           'Costo original: ${monto_original}' . "\n" .
                           'Nuevo costo: ${monto_bonificado}' . "\n\n",
                'descripcion' => '[Reparaciones - Comunicación] Template de bonificación para insertar en mensaje de demora. Variables: {porcentaje}, {monto_original}, {monto_bonificado}',
            ],
        ];

        foreach ($configuraciones as $config) {
            \App\Models\Configuracion::updateOrCreate(
                ['clave' => $config['clave']],
                $config
            );
        }

        $this->command->info('✓ 10 configuraciones de reparaciones creadas/actualizadas exitosamente');
    }
}
