<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notificaciones_whatsapp') && Schema::hasColumn('notificaciones_whatsapp', 'estado_envio')) {
            Schema::table('notificaciones_whatsapp', function (Blueprint $table) {
                $table->foreignId('estado_envio_id')->nullable()->after('estado_envio')
                    ->constrained('estados_envio_whatsapp', 'estado_id')->onDelete('restrict');
            });
            DB::statement("UPDATE notificaciones_whatsapp nw JOIN estados_envio_whatsapp eew ON nw.estado_envio = eew.nombre SET nw.estado_envio_id = eew.estado_id");
            Schema::table('notificaciones_whatsapp', function (Blueprint $table) {
                $table->dropColumn('estado_envio');
                $table->unsignedBigInteger('estado_envio_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notificaciones_whatsapp')) {
            Schema::table('notificaciones_whatsapp', function (Blueprint $table) {
                $table->string('estado_envio', 20)->default('Pendiente');
            });
            DB::statement("UPDATE notificaciones_whatsapp nw JOIN estados_envio_whatsapp eew ON nw.estado_envio_id = eew.estado_id SET nw.estado_envio = eew.nombre");
            Schema::table('notificaciones_whatsapp', function (Blueprint $table) {
                $table->dropForeign(['estado_envio_id']);
                $table->dropColumn('estado_envio_id');
            });
        }
    }
};
