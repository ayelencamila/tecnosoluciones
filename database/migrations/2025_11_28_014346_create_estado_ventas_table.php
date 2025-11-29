<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_venta', function (Blueprint $table) {
            $table->id('estadoVentaID'); // PK
            $table->string('nombreEstado', 50)->unique(); // Pendiente, Completada, Anulada
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_venta');
    }
};
