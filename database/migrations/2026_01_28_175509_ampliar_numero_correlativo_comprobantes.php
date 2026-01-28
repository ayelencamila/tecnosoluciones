<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Amplía numero_correlativo de 20 a 50 caracteres para soportar formatos largos
     * como REC-20260128-170257-182994 (26 chars)
     */
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('numero_correlativo', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('numero_correlativo', 20)->change();
        });
    }
};
