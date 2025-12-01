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
        Schema::table('configuracion', function (Blueprint $table) {
            // Cambiar de VARCHAR(255) a TEXT para permitir templates largos
            $table->text('valor')->change();
            $table->text('descripcion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            // Revertir a VARCHAR(255)
            $table->string('valor', 255)->change();
            $table->string('descripcion', 255)->nullable()->change();
        });
    }
};
