<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            // Eliminar la FK existente para poder modificar la columna
            $table->dropForeign(['orden_compra_id']);

            // Hacer nullable para recepciones directas (sin OC)
            $table->foreignId('orden_compra_id')->nullable()->change();

            // Re-crear FK nullable
            $table->foreign('orden_compra_id')
                  ->references('id')->on('ordenes_compra')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepciones_mercaderia', function (Blueprint $table) {
            $table->dropForeign(['orden_compra_id']);
            $table->foreignId('orden_compra_id')->nullable(false)->change();
            $table->foreign('orden_compra_id')
                  ->references('id')->on('ordenes_compra')
                  ->onDelete('restrict');
        });
    }
};
