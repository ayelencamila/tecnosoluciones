<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->foreignId('tipo_movimiento_id')->nullable()->after('productoID')
                ->constrained('tipos_movimiento_stock', 'id')->onDelete('restrict');
        });
        DB::statement("UPDATE movimientos_stock ms JOIN tipos_movimiento_stock tms ON ms.tipoMovimiento = tms.nombre SET ms.tipo_movimiento_id = tms.id");
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropColumn('tipoMovimiento');
            $table->unsignedBigInteger('tipo_movimiento_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->enum('tipoMovimiento', ['ENTRADA', 'SALIDA', 'AJUSTE']);
        });
        DB::statement("UPDATE movimientos_stock ms JOIN tipos_movimiento_stock tms ON ms.tipo_movimiento_id = tms.id SET ms.tipoMovimiento = tms.nombre");
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropForeign(['tipo_movimiento_id']);
            $table->dropColumn('tipo_movimiento_id');
        });
    }
};
