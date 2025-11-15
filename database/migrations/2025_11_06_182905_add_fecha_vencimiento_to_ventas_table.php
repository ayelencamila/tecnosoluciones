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
        Schema::table('ventas', function (Blueprint $table) {
            // Añade la columna fecha_vencimiento después de fecha_venta
            $table->date('fecha_vencimiento')->nullable()->after('fecha_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Elimina la columna fecha_vencimiento si se revierte la migración
            $table->dropColumn('fecha_vencimiento');
        });
    }
};
