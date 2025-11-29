<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id('descuento_id');
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('descripcion');

            // --- CAMBIO: Usamos las nuevas tablas maestras ---
            $table->foreignId('tipo_descuento_id')->constrained('tipos_descuento');
            $table->foreignId('aplicabilidad_descuento_id')->constrained('aplicabilidades_descuento');
            // -----------------------------------------------

            $table->decimal('valor', 15, 2);
            $table->boolean('activo')->default(true);
            $table->date('valido_desde')->nullable();
            $table->date('valido_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};