<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_movimiento_stock', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // Ej: Entrada, Venta, Ajuste
            $table->integer('signo'); // 1 (Suma), -1 (Resta), 0 (Neutro/Reemplazo)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_movimiento_stock');
    }
};