<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->foreignId('tipo_movimiento_cc_id')->nullable()->after('cuentaCorrienteID')
                ->constrained('tipos_movimiento_cuenta_corriente', 'tipo_id')->onDelete('restrict');
        });
        DB::statement("UPDATE movimientos_cuenta_corriente mcc JOIN tipos_movimiento_cuenta_corriente tmcc ON mcc.tipoMovimiento = tmcc.nombre SET mcc.tipo_movimiento_cc_id = tmcc.tipo_id");
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->dropColumn('tipoMovimiento');
            $table->unsignedBigInteger('tipo_movimiento_cc_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->enum('tipoMovimiento', ['Debito', 'Credito']);
        });
        DB::statement("UPDATE movimientos_cuenta_corriente mcc JOIN tipos_movimiento_cuenta_corriente tmcc ON mcc.tipo_movimiento_cc_id = tmcc.tipo_id SET mcc.tipoMovimiento = tmcc.nombre");
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->dropForeign(['tipo_movimiento_cc_id']);
            $table->dropColumn('tipo_movimiento_cc_id');
        });
    }
};
