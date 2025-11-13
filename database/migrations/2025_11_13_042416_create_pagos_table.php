<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('pago_id'); // PK personalizada

            // Cliente que paga
            $table->unsignedBigInteger('clienteID');
            $table->foreign('clienteID')->references('clienteID')->on('clientes');

            // Usuario que cobra (Cajero/Admin)
            $table->foreignId('user_id')->constrained('users');

            // Detalles del Pago
            $table->decimal('monto', 15, 2);
            $table->string('metodo_pago', 50); // 'efectivo', 'transferencia', 'cheque'
            $table->dateTime('fecha_pago');
            
            // Comprobante interno (Recibo X)
            $table->string('numero_recibo', 100)->unique();
            
            $table->text('observaciones')->nullable();
            $table->boolean('anulado')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
