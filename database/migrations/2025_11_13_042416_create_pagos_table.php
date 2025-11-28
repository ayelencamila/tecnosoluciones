<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            // PK
            $table->id('pagoID');

            // Relaciones
            $table->foreignId('clienteID')->constrained('clientes', 'clienteID');
            $table->foreignId('user_id')->constrained('users'); // El cajero
            $table->foreignId('medioPagoID')->constrained('medios_pago', 'medioPagoID');

            // Datos del Pago
            $table->string('numero_recibo', 100)->unique(); // <--- LA COLUMNA FALTANTE
            $table->decimal('monto', 15, 2);
            $table->dateTime('fecha_pago');
            $table->text('observaciones')->nullable();
            
            // Estado
            $table->boolean('anulado')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
