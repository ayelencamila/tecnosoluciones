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
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->enum('decision_cliente', ['pendiente', 'aceptar', 'cancelar'])
                  ->default('pendiente')
                  ->after('observaciones_aprobacion')
                  ->comment('Decisión del cliente sobre la bonificación');
            
            $table->timestamp('fecha_decision_cliente')
                  ->nullable()
                  ->after('decision_cliente')
                  ->comment('Fecha en que el cliente tomó la decisión');
            
            $table->text('observaciones_decision')
                  ->nullable()
                  ->after('fecha_decision_cliente')
                  ->comment('Observaciones adicionales sobre la decisión del cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonificaciones_reparacion', function (Blueprint $table) {
            $table->dropColumn(['decision_cliente', 'fecha_decision_cliente', 'observaciones_decision']);
        });
    }
};
