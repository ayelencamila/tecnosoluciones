<?php

// ... (resto del archivo)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id('configuracionID'); // PK
            $table->string('clave')->unique(); // Por ejemplo: 'dias_gracia_global', 'bloqueo_automatico_cc'
            $table->string('valor'); // El valor de la configuración
            $table->string('descripcion')->nullable(); // Descripción para el admin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
