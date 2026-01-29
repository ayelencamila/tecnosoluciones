<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega plantilla de WhatsApp para notificar rechazo de bonificación al cliente (CU-15)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plantillas_whatsapp')->insert([
            'tipo_evento' => 'rechazo_bonificacion_cliente',
            'nombre' => 'Notificación de Rechazo de Bonificación',
            'contenido_plantilla' => "Hola {nombre_cliente} 👋

Lamentamos informarle que no ha sido posible continuar con la reparación de su equipo *{equipo_marca} {equipo_modelo}* (código: *{codigo_reparacion}*).

📋 *Motivo:* {motivo_rechazo}

Le solicitamos que se acerque a nuestro local para *retirar su equipo*. Si corresponde, se le realizará la devolución del dinero abonado.

Lamentamos los inconvenientes ocasionados y quedamos a su disposición para cualquier consulta.

Atentamente,
*{nombre_empresa}*",
            'variables_disponibles' => json_encode([
                'nombre_cliente',
                'apellido_cliente',
                'codigo_reparacion',
                'equipo_marca',
                'equipo_modelo',
                'motivo_rechazo',
                'nombre_empresa',
            ]),
            'horario_inicio' => '09:00:00',
            'horario_fin' => '20:00:00',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('plantillas_whatsapp')
            ->where('tipo_evento', 'rechazo_bonificacion_cliente')
            ->delete();
    }
};
