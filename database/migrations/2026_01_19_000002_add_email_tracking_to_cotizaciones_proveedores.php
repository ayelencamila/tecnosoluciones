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
            $table->boolean('enviado_email')->default(false)->after('fecha_envio');
            $table->dateTime('fecha_envio_email')->nullable()->after('enviado_email');
            $table->text('error_envio_email')->nullable()->after('fecha_envio_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones_proveedores', function (Blueprint $table) {
            $table->dropColumn(['enviado_email', 'fecha_envio_email', 'error_envio_email']);
        });
    }
};
