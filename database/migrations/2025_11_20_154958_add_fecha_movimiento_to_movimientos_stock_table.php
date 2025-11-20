<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            // Agregamos SOLO la columna que falta
            if (!Schema::hasColumn('movimientos_stock', 'fecha_movimiento')) {
                $table->dateTime('fecha_movimiento')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos_stock', 'fecha_movimiento')) {
                $table->dropColumn('fecha_movimiento');
            }
        });
    }
};