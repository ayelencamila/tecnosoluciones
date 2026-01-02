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

            // Relación con Cliente
            $table->foreignId('clienteID')
                  ->constrained('clientes', 'clienteID')
                  ->onDelete('restrict'); 

            // Relación con Técnico (Usuario del sistema)
            $table->foreignId('tecnico_id')
                  ->nullable() 
                  ->constrained('users') 
                  ->onDelete('restrict');

            // Relación con Estado de Reparación
            $table->foreignId('estado_reparacion_id')
                  ->constrained('estados_reparacion', 'estadoReparacionID')
                  ->onDelete('restrict');

            // Datos del Equipo
            $table->string('codigo_reparacion', 50)->unique(); 
            $table->string('numero_serie_imei', 100)->nullable(); 
            $table->string('clave_bloqueo', 100)->nullable();
            $table->text('accesorios_dejados')->nullable();

            $table->foreignId('modelo_id')
                  ->constrained('modelos') 
                  ->onDelete('restrict');
            
            // Diagnóstico y Detalles
            $table->text('falla_declarada');           
            $table->text('diagnostico_tecnico')->nullable();
            $table->text('observaciones')->nullable();

            // Fechas y SLA
            $table->dateTime('fecha_ingreso');
            $table->dateTime('fecha_promesa')->nullable();
            $table->dateTime('fecha_entrega_real')->nullable();

            // Totales (Desnormalización controlada para historial de precios)
            $table->decimal('costo_mano_obra', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2)->default(0);

            // Banderas de estado
            $table->boolean('anulada')->default(false);

            $table->timestamps();
            $table->softDeletes(); // Importante para no perder historial
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