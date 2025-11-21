<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reparaciones', function (Blueprint $table) {
            // PK personalizada
            $table->id('reparacionID'); 

            // Relación con Cliente (según convención clienteID)
            $table->foreignId('clienteID')
                  ->constrained('clientes', 'clienteID')
                  ->onDelete('restrict'); // No borrar cliente si tiene reparaciones

            // Relación con Técnico (Usuario del sistema)
            $table->foreignId('tecnico_id')
                  ->nullable() // Al inicio puede no tener técnico asignado
                  ->constrained('users')
                  ->onDelete('restrict');

            // Relación con Estado de Reparación
            $table->foreignId('estado_reparacion_id')
                  ->constrained('estados_reparacion', 'estadoReparacionID');

            // Datos del Equipo (Requisitos IRQ-06)
            $table->string('codigo_reparacion')->unique(); // Ej: REP-2025-001
            $table->string('equipo_marca');      // Ej: Samsung
            $table->string('equipo_modelo');     // Ej: Galaxy S20
            $table->string('numero_serie_imei')->nullable(); // Vital para seguridad
            $table->string('clave_bloqueo')->nullable();     // Patrón/PIN
            $table->text('accesorios_dejados')->nullable();  // Ej: "Funda, sin cargador"

            // Diagnóstico y Detalles
            $table->text('falla_declarada');           // Lo que dice el cliente
            $table->text('diagnostico_tecnico')->nullable(); // Lo que dice el técnico
            $table->text('observaciones')->nullable();       // Notas internas

            // Fechas para SLA y Control
            $table->dateTime('fecha_ingreso');
            $table->dateTime('fecha_promesa')->nullable();      // SLA
            $table->dateTime('fecha_entrega_real')->nullable(); // Cierre

            // Totales (Opcional: para congelar el precio histórico)
            $table->decimal('costo_mano_obra', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2)->default(0);

            // Banderas de estado
            $table->boolean('anulada')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reparaciones');
    }
};