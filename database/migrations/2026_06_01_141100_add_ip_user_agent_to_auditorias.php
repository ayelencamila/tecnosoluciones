<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega contexto del cliente al log de auditoria: direccion IP y User-Agent.
 *
 * En un entorno web moderno varios usuarios comparten la misma IP publica
 * (ej. la red del local), por lo que el User-Agent (navegador/dispositivo)
 * complementa la trazabilidad. Ambas columnas se capturan de forma
 * centralizada en el hook `creating` del modelo Auditoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            if (! Schema::hasColumn('auditorias', 'ip')) {
                $table->string('ip', 45)->nullable()->after('detalles'); // 45 = IPv6 max
            }
            if (! Schema::hasColumn('auditorias', 'user_agent')) {
                $table->string('user_agent', 512)->nullable()->after('ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            if (Schema::hasColumn('auditorias', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('auditorias', 'ip')) {
                $table->dropColumn('ip');
            }
        });
    }
};
