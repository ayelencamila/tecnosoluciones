<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega plantilla de WhatsApp para notificar demora de reparación al cliente (CU-14 Paso 8)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plantillas_whatsapp')->insert([
            'tipo_evento' => 'demora_reparacion_cliente',
            'nombre' => 'Notificación de Demora al Cliente',
            'contenido_plantilla' => "Hola {nombre_cliente} 👋

Le informamos que la reparación de su equipo *{equipo_marca} {equipo_modelo}* (código: *{codigo_reparacion}*) está experimentando una demora.

📅 Fecha de ingreso: {fecha_ingreso}
⏰ Días de demora: {dias_excedidos}

Lamentamos los inconvenientes causados. {tecnico_nombre} está trabajando en su equipo y nos pondremos en contacto a la brevedad para informarle sobre el estado y posibles compensaciones.

Si tiene alguna consulta, no dude en contactarnos.

Atentamente,
*{nombre_empresa}*",
            'variables_disponibles' => json_encode([
                'nombre_cliente',
                'apellido_cliente',
                'codigo_reparacion',
                'equipo_marca',
                'equipo_modelo',
                'dias_excedidos',
                'fecha_ingreso',
                'fecha_promesa',
                'tecnico_nombre',
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
            ->where('tipo_evento', 'demora_reparacion_cliente')
            ->delete();
    }
};
