<?php

namespace Database\Seeders;

use App\Models\PlantillaWhatsapp;
use Illuminate\Database\Seeder;

class PlantillasWhatsappSeeder extends Seeder
{
    /**
     * Seed de plantillas WhatsApp (CU-30)
     * 
     * Plantillas activas y en uso:
     * - bonificacion_cliente: CU-14/15 Oferta de bonificación al cliente
     * - rechazo_bonificacion_cliente: CU-14/15 Notificación de rechazo
     * - bloqueo_cc: CU-09 Notificación de bloqueo de cuenta
     * - solicitud_cotizacion: CU-20 Solicitud a proveedores
     * - recordatorio_cotizacion: CU-20 Recordatorio a proveedores
     * - orden_compra: CU-22 Orden de compra a proveedores
     */
    public function run(): void
    {
        $plantillas = [
            // ===== BONIFICACIONES DE REPARACIONES (CU-14/15) =====
            [
                'tipo_evento' => 'bonificacion_cliente',
                'nombre' => 'Notificación de bonificación por demora al cliente',
                'contenido_plantilla' => "🎁 *BONIFICACIÓN POR DEMORA - Reparación #{codigo_reparacion}*\n\n" .
                    "Estimado/a {nombre_cliente},\n\n" .
                    "Lamentamos informarle que su reparación ha excedido el tiempo estimado.\n\n" .
                    "📱 Equipo: {equipo_marca} {equipo_modelo}\n" .
                    "⏰ Ingresado: {fecha_ingreso}\n" .
                    "📊 Días de demora: {dias_excedidos}\n\n" .
                    "Como compensación, aplicaremos una *bonificación del {porcentaje}%* sobre el costo final.\n\n" .
                    "💰 Monto original: \${monto_original}\n" .
                    "🎉 Descuento: -{porcentaje}% (-\${monto_bonificado})\n" .
                    "💳 Total a pagar: \${monto_final}\n\n" .
                    "Motivo: {motivo_demora}\n\n" .
                    "⚠️ *IMPORTANTE: Necesitamos su decisión*\n\n" .
                    "Por favor, indíquenos si desea:\n" .
                    "✅ *CONTINUAR* con la reparación y aplicar la bonificación\n" .
                    "❌ *CANCELAR* y retirar su equipo\n\n" .
                    "👉 Responda aquí: {url_respuesta}\n\n" .
                    "Gracias por su comprensión.",
                'variables_disponibles' => [
                    'codigo_reparacion',
                    'nombre_cliente',
                    'equipo_marca',
                    'equipo_modelo',
                    'fecha_ingreso',
                    'dias_excedidos',
                    'porcentaje',
                    'monto_original',
                    'monto_bonificado',
                    'monto_final',
                    'motivo_demora',
                    'url_respuesta',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '20:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],

            // ===== RECHAZO DE BONIFICACIÓN (CU-14/15) =====
            [
                'tipo_evento' => 'rechazo_bonificacion_cliente',
                'nombre' => 'Notificación de Rechazo de Bonificación',
                'contenido_plantilla' => "❌ *CANCELACIÓN CONFIRMADA - Reparación #{codigo_reparacion}*\n\n" .
                    "Estimado/a {nombre_cliente},\n\n" .
                    "Hemos recibido su decisión de *NO CONTINUAR* con la reparación.\n\n" .
                    "📱 Equipo: {equipo_marca} {equipo_modelo}\n\n" .
                    "Su equipo estará disponible para retiro en nuestro local.\n\n" .
                    "Por favor, coordine el retiro dentro de los próximos 7 días.\n\n" .
                    "Gracias por contactarnos.\n" .
                    "*TecnoSoluciones*",
                'variables_disponibles' => [
                    'codigo_reparacion',
                    'nombre_cliente',
                    'equipo_marca',
                    'equipo_modelo',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '20:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],

            // ===== CUENTA CORRIENTE: BLOQUEO (CU-09) =====
            [
                'tipo_evento' => 'bloqueo_cc',
                'nombre' => 'Notificación de bloqueo de cuenta corriente',
                'contenido_plantilla' => "🚨 *CUENTA BLOQUEADA - TecnoSoluciones*\n\n" .
                    "Hola {nombre_cliente},\n\n" .
                    "Le informamos que su cuenta corriente ha sido *BLOQUEADA* por el siguiente motivo:\n\n" .
                    "{motivo}\n\n" .
                    "Por favor, comuníquese con nosotros para regularizar su situación.\n\n" .
                    "Gracias por su atención.",
                'variables_disponibles' => [
                    'nombre_cliente',
                    'motivo',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],

            // ===== SOLICITUD DE COTIZACIÓN (CU-20) =====
            [
                'tipo_evento' => 'solicitud_cotizacion',
                'nombre' => 'Solicitud de cotización a proveedores',
                'contenido_plantilla' => "📋 *SOLICITUD DE COTIZACIÓN*\n\n" .
                    "Estimado/a *{razon_social}*,\n\n" .
                    "Le invitamos a cotizar los siguientes productos:\n\n" .
                    "{lista_productos}\n\n" .
                    "*Fecha límite:* {fecha_vencimiento}\n\n" .
                    "🔗 *Para cotizar, ingrese al siguiente enlace:*\n" .
                    "{magic_link}\n\n" .
                    "_Este enlace es único y personal. No lo comparta._\n\n" .
                    "Gracias por su colaboración.\n" .
                    "*TecnoSoluciones*",
                'variables_disponibles' => [
                    'razon_social',
                    'lista_productos',
                    'fecha_vencimiento',
                    'magic_link',
                    'codigo_solicitud',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],

            // ===== RECORDATORIO DE COTIZACIÓN (CU-20) =====
            [
                'tipo_evento' => 'recordatorio_cotizacion',
                'nombre' => 'Recordatorio de cotización pendiente',
                'contenido_plantilla' => "🔔 *RECORDATORIO - SOLICITUD DE COTIZACIÓN*\n\n" .
                    "Estimado/a *{razon_social}*,\n\n" .
                    "Le recordamos que tenemos una solicitud de cotización pendiente.\n" .
                    "⏰ *Solo quedan {dias_restantes} día(s) para responder.*\n\n" .
                    "*Productos solicitados:*\n\n" .
                    "{lista_productos}\n\n" .
                    "*Fecha límite:* {fecha_vencimiento}\n\n" .
                    "🔗 *Para cotizar, ingrese al siguiente enlace:*\n" .
                    "{magic_link}\n\n" .
                    "_Este enlace es único y personal. No lo comparta._\n\n" .
                    "Gracias por su colaboración.\n" .
                    "*TecnoSoluciones*",
                'variables_disponibles' => [
                    'razon_social',
                    'dias_restantes',
                    'lista_productos',
                    'fecha_vencimiento',
                    'magic_link',
                    'codigo_solicitud',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],

            // ===== ORDEN DE COMPRA (CU-22) =====
            [
                'tipo_evento' => 'orden_compra',
                'nombre' => 'Envío de orden de compra a proveedor',
                'contenido_plantilla' => "📦 *ORDEN DE COMPRA - {numero_oc}*\n\n" .
                    "Estimado/a *{razon_social}*,\n\n" .
                    "Le enviamos la siguiente orden de compra:\n\n" .
                    "{lista_productos}\n\n" .
                    "💰 *Total:* \${total}\n\n" .
                    "📅 *Fecha esperada de entrega:* {fecha_entrega}\n\n" .
                    "Por favor, confirme la recepción de esta orden.\n\n" .
                    "Gracias.\n" .
                    "*TecnoSoluciones*",
                'variables_disponibles' => [
                    'numero_oc',
                    'razon_social',
                    'lista_productos',
                    'total',
                    'fecha_entrega',
                    'observaciones',
                ],
                'horario_inicio' => '09:00',
                'horario_fin' => '18:00',
                'activo' => true,
                'motivo_modificacion' => 'Plantilla inicial del sistema',
                'usuario_modificacion' => null,
            ],
        ];

        foreach ($plantillas as $plantilla) {
            PlantillaWhatsapp::updateOrCreate(
                ['tipo_evento' => $plantilla['tipo_evento']],
                $plantilla
            );
        }

        $this->command->info('✅ ' . count($plantillas) . ' plantillas WhatsApp creadas/actualizadas exitosamente');
    }
}
