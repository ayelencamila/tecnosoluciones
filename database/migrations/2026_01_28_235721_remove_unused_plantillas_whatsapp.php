<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Eliminar plantillas WhatsApp no utilizadas
 * 
 * Se eliminan las siguientes plantillas que NO están siendo usadas:
 * - admin_alert_cc: Alertas admin van por campanita, no WhatsApp
 * - alerta_sla_tecnico: CU-14 usa SOLO campanita para técnicos
 * - demora_reparacion_cliente: Job existe pero nunca se despacha
 * - recordatorio_cc: No existe código que lo despache
 * - revision_cc: Solo se usa 'bloqueo' como acción real
 * 
 * Se mantienen las que SÍ se usan:
 * - bloqueo_cc: CU-09 notificación de bloqueo
 * - bonificacion_cliente: CU-14/15 oferta de bonificación
 * - rechazo_bonificacion_cliente: CU-14/15 notificación de rechazo
 */
return new class extends Migration
{
    /**
     * Plantillas a eliminar (tipo_evento)
     */
    private array $plantillasAEliminar = [
        'admin_alert_cc',
        'alerta_sla_tecnico',
        'demora_reparacion_cliente',
        'recordatorio_cc',
        'revision_cc',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plantillas_whatsapp')
            ->whereIn('tipo_evento', $this->plantillasAEliminar)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar las plantillas eliminadas
        $plantillas = [
            [
                'tipo_evento' => 'alerta_sla_tecnico',
                'nombre' => 'Alerta de SLA excedido a técnicos',
                'contenido_plantilla' => "⚠️ *ALERTA SLA - Reparación #{codigo_reparacion}*\n\n" .
                    "Técnico: {nombre_tecnico}\nCliente: {nombre_cliente}\n" .
                    "Equipo: {equipo_marca} {equipo_modelo}\n\n" .
                    "📊 Estado del SLA:\n• SLA vigente: {sla_vigente} días\n" .
                    "• Días efectivos: {dias_efectivos} días\n• Días excedidos: {dias_excedidos} días\n" .
                    "• Tipo: {tipo_alerta}\n\n⏰ Fecha de ingreso: {fecha_ingreso}",
                'variables_disponibles' => json_encode(['codigo_reparacion', 'nombre_tecnico', 'nombre_cliente', 'equipo_marca', 'equipo_modelo', 'sla_vigente', 'dias_efectivos', 'dias_excedidos', 'tipo_alerta', 'fecha_ingreso']),
                'horario_inicio' => '08:00',
                'horario_fin' => '21:00',
                'activo' => true,
                'motivo_modificacion' => 'Restauración desde rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_evento' => 'revision_cc',
                'nombre' => 'Notificación de cuenta en revisión',
                'contenido_plantilla' => "⚠️ *CUENTA EN REVISIÓN - TecnoSoluciones*\n\n" .
                    "Hola {nombre_cliente},\n\nSu cuenta corriente está actualmente en *REVISIÓN* debido a:\n\n" .
                    "{motivo}\n\nLe recomendamos ponerse en contacto con nosotros.\n\nGracias.",
                'variables_disponibles' => json_encode(['nombre_cliente', 'motivo']),
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Restauración desde rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_evento' => 'admin_alert_cc',
                'nombre' => 'Alerta al administrador por incumplimiento de CC',
                'contenido_plantilla' => "🚨 *ALERTA ADMIN - TecnoSoluciones*\n\n" .
                    "Cliente: {nombre_cliente}\nMotivo: {motivo}\n\nRequiere atención inmediata.",
                'variables_disponibles' => json_encode(['nombre_cliente', 'motivo']),
                'horario_inicio' => '00:00',
                'horario_fin' => '23:59',
                'activo' => true,
                'motivo_modificacion' => 'Restauración desde rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_evento' => 'recordatorio_cc',
                'nombre' => 'Recordatorio de saldo pendiente',
                'contenido_plantilla' => "💳 *RECORDATORIO - TecnoSoluciones*\n\n" .
                    "Hola {nombre_cliente},\n\nLe recordamos que tiene un saldo pendiente.\n\n" .
                    "Por favor, regularice su situación.\n\nGracias.",
                'variables_disponibles' => json_encode(['nombre_cliente', 'saldo_pendiente']),
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Restauración desde rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_evento' => 'demora_reparacion_cliente',
                'nombre' => 'Notificación de Demora al Cliente',
                'contenido_plantilla' => "⏰ *AVISO DE DEMORA - Reparación #{codigo_reparacion}*\n\n" .
                    "Estimado/a {nombre_cliente},\n\nLe informamos que su reparación está demorando más de lo previsto.\n\n" .
                    "Equipo: {equipo_marca} {equipo_modelo}\nDías excedidos: {dias_excedidos}\n\n" .
                    "Nos pondremos en contacto pronto.\n\nGracias por su paciencia.",
                'variables_disponibles' => json_encode(['codigo_reparacion', 'nombre_cliente', 'equipo_marca', 'equipo_modelo', 'dias_excedidos']),
                'horario_inicio' => '09:00',
                'horario_fin' => '20:00',
                'activo' => true,
                'motivo_modificacion' => 'Restauración desde rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plantillas as $plantilla) {
            DB::table('plantillas_whatsapp')->insert($plantilla);
        }
    }
};
