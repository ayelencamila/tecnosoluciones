<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('pagos', function (Blueprint $table) {
        $table->id('pagoID');
        $table->foreignId('clienteID')->constrained('clientes', 'clienteID');

        // CAMBIO IMPORTANTE:
        // Antes: $table->string('metodo_pago'); 
        // Ahora:
        $table->foreignId('medioPagoID')->constrained('medios_pago', 'medioPagoID');

        $table->decimal('monto', 15, 2);
        $table->text('observaciones')->nullable();
        // ... otros campos ...
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
