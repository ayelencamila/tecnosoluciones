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
        Schema::table('reparaciones', function (Blueprint $table) {
            // Control de SLA
            $table->boolean('sla_excedido')->default(false)
                  ->after('anulada')
                  ->comment('Marca si la reparación excedió el SLA vigente');
            
            $table->timestamp('fecha_marcada_demorada')->nullable()
                  ->after('sla_excedido')
                  ->comment('Fecha en que el sistema detectó la demora');
            
            // Decisión del cliente ante demora
            $table->enum('decision_cliente', ['continuar', 'cancelar', 'pendiente'])
                  ->default('pendiente')
                  ->after('fecha_marcada_demorada')
                  ->comment('Decisión del cliente cuando se le notifica la demora');
            
            $table->timestamp('fecha_decision_cliente')->nullable()
                  ->after('decision_cliente');
            
            // Índice para búsquedas de reparaciones demoradas
            $table->index('sla_excedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropIndex(['sla_excedido']);
            $table->dropColumn([
                'sla_excedido',
                'fecha_marcada_demorada',
                'decision_cliente',
                'fecha_decision_cliente'
            ]);
        });
    }
};
