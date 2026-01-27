<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->foreignId('tipo_comprobante_id')->nullable()->after('usuario_id')
                ->constrained('tipos_comprobante', 'tipo_id')->onDelete('restrict');
            $table->foreignId('estado_comprobante_id')->nullable()->after('ruta_archivo')
                ->constrained('estados_comprobante', 'estado_id')->onDelete('restrict');
        });

        // Migrar tipo_comprobante
        DB::statement("
            UPDATE comprobantes c
            JOIN tipos_comprobante tc ON c.tipo_comprobante = tc.codigo
            SET c.tipo_comprobante_id = tc.tipo_id
        ");

        // Migrar estado
        DB::statement("
            UPDATE comprobantes c
            JOIN estados_comprobante ec ON c.estado = ec.nombre
            SET c.estado_comprobante_id = ec.estado_id
        ");

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn(['tipo_comprobante', 'estado']);
            $table->unsignedBigInteger('tipo_comprobante_id')->nullable(false)->change();
            $table->unsignedBigInteger('estado_comprobante_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->enum('tipo_comprobante', ['TICKET','FACTURA_A','FACTURA_B','ORDEN_SERVICIO','RECIBO_PAGO','ORDEN_COMPRA'])->after('usuario_id');
            $table->enum('estado', ['EMITIDO', 'ANULADO', 'REEMPLAZADO'])->default('EMITIDO')->after('ruta_archivo');
        });
        
        DB::statement("UPDATE comprobantes c JOIN tipos_comprobante tc ON c.tipo_comprobante_id = tc.tipo_id SET c.tipo_comprobante = tc.codigo");
        DB::statement("UPDATE comprobantes c JOIN estados_comprobante ec ON c.estado_comprobante_id = ec.estado_id SET c.estado = ec.nombre");
        
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign(['tipo_comprobante_id','estado_comprobante_id']);
            $table->dropColumn(['tipo_comprobante_id','estado_comprobante_id']);
        });
    }
};
