<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU-23: Detalle de Recepciones de Mercadería
 * 
 * Entidad débil que registra qué productos y cantidades fueron recibidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_recepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('recepciones_mercaderia')->onDelete('cascade');
            $table->foreignId('detalle_orden_id')->constrained('detalle_ordenes_compra')->onDelete('restrict');
            // producto_id NO se incluye (3FN): se obtiene vía detalle_orden_id → producto_id
            $table->integer('cantidad_recibida');
            $table->text('observacion_item')->nullable(); // Observación por producto si aplica
            $table->timestamps();

            $table->index('recepcion_id');
            $table->index('detalle_orden_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_recepciones');
    }
};
