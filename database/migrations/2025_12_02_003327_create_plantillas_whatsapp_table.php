<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * CU-30: Configurar Plantillas y Horarios de Envío WhatsApp
     */
    public function up(): void
    {
        Schema::create('plantillas_whatsapp', function (Blueprint $table) {
            $table->id('plantilla_id');
            
            // Identificación
            $table->string('tipo_evento', 100)->unique()->comment('Identificador único del tipo de evento (ej: bonificacion_cliente, alerta_sla_tecnico, bloqueo_cc)');
            $table->string('nombre', 200)->comment('Nombre descriptivo de la plantilla');
            
            // Contenido
            $table->text('contenido_plantilla')->comment('Texto del mensaje con variables entre llaves {variable}');
            $table->json('variables_disponibles')->comment('Array de variables permitidas para esta plantilla');
            
            // Horarios de envío (CU-30 Paso 6)
            $table->time('horario_inicio')->default('09:00')->comment('Hora mínima para envío de mensajes');
            $table->time('horario_fin')->default('20:00')->comment('Hora máxima para envío de mensajes');
            
            // Estado y control
            $table->boolean('activo')->default(true)->comment('Si está habilitado para uso');
            
            // Auditoría de cambios (CU-30 Paso 7-8)
            $table->text('motivo_modificacion')->nullable()->comment('Motivo del último cambio (CU-30)');
            $table->foreignId('usuario_modificacion')->nullable()->constrained('users')->comment('Usuario que realizó el último cambio');
            
            $table->timestamps();
            
            // Índices
            $table->index('tipo_evento');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_whatsapp');
    }
};
