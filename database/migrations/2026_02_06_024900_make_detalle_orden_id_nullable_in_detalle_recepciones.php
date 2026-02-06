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
        Schema::table('detalle_recepciones', function (Blueprint $table) {
            $table->dropForeign(['detalle_orden_id']);
            $table->foreignId('detalle_orden_id')->nullable()->change();
            $table->foreign('detalle_orden_id')
                  ->references('id')->on('detalle_ordenes_compra')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_recepciones', function (Blueprint $table) {
            $table->dropForeign(['detalle_orden_id']);
            $table->foreignId('detalle_orden_id')->nullable(false)->change();
            $table->foreign('detalle_orden_id')
                  ->references('id')->on('detalle_ordenes_compra')
                  ->onDelete('restrict');
        });
    }
};
