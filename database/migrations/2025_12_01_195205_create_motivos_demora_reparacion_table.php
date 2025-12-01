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
        Schema::create('motivos_demora_reparacion', function (Blueprint $table) {
            $table->id('motivoDemoraID');
            
            $table->string('codigo', 50)->unique()->comment('Código único para identificación en código');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            
            // Configuración del motivo
            $table->boolean('requiere_bonificacion')->default(true)
                  ->comment('Indica si este motivo califica para bonificación');
            $table->boolean('pausa_sla')->default(false)
                  ->comment('Si true, este motivo pausa el conteo de días de SLA');
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Orden para mostrar en listas
            $table->integer('orden')->default(0);
            
            $table->timestamps();
            
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motivos_demora_reparacion');
    }
};
