<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            // Estado de pago: pendiente, pagado, parcial
            $table->string('estado_pago', 20)->default('pendiente')->after('total_final');
            $table->decimal('monto_cobrado', 15, 2)->default(0)->after('estado_pago');
            $table->foreignId('medio_pago_id')->nullable()->after('monto_cobrado')
                  ->constrained('medios_pago', 'medioPagoID')->nullOnDelete();
            $table->timestamp('fecha_cobro')->nullable()->after('medio_pago_id');
            $table->unsignedBigInteger('cobrado_por')->nullable()->after('fecha_cobro');

            $table->foreign('cobrado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropForeign(['cobrado_por']);
            $table->dropForeign(['medio_pago_id']);
            $table->dropColumn(['estado_pago', 'monto_cobrado', 'medio_pago_id', 'fecha_cobro', 'cobrado_por']);
        });
    }
};
