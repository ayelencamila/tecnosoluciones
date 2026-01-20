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
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dateTime('fecha_recordatorio')->nullable()->after('fecha_envio_email')
                ->comment('Fecha del último recordatorio enviado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dropColumn('fecha_recordatorio');
        });
    }
};
