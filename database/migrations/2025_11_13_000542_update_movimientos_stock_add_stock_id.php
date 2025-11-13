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
        Schema::table('movimientos_stock', function (Blueprint $table) {
            // Agregar nueva columna stock_id
            $table->foreignId('stock_id')->nullable()->after('id')->constrained('stock', 'stock_id')->onDelete('restrict');

            // Hacer productoID nullable (temporal para migración de datos)
            $table->foreignId('productoID')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            // Eliminar stock_id
            $table->dropForeign(['stock_id']);
            $table->dropColumn('stock_id');

            // Restaurar productoID como NOT NULL
            $table->foreignId('productoID')->nullable(false)->change();
        });
    }
};
