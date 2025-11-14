<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productoID')->constrained('productos');
            $table->enum('tipoMovimiento', ['ENTRADA', 'SALIDA', 'AJUSTE']);
            $table->integer('cantidad');
            $table->integer('stockAnterior');
            $table->integer('stockNuevo');
            $table->string('motivo', 255)->nullable();
            $table->integer('referenciaID')->nullable();
            $table->string('referenciaTabla', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
