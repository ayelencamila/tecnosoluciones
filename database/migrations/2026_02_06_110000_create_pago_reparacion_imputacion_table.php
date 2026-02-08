<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_reparacion_imputacion', function (Blueprint $table) {
            $table->id();

            // El pago que se está distribuyendo
            $table->foreignId('pago_id')
                  ->constrained(table: 'pagos', column: 'pagoID')
                  ->onDelete('cascade');

            // La reparación (deuda) que se está cancelando
            $table->foreignId('reparacion_id')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('restrict');

            // Cuánto dinero de este pago va a esta reparación
            $table->decimal('monto_imputado', 15, 2);

            $table->timestamps();

            // Evitar duplicados: un pago no puede imputar dos veces a la misma reparación
            $table->unique(['pago_id', 'reparacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_reparacion_imputacion');
    }
};
