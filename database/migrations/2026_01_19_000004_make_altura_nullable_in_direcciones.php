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
        Schema::table('direcciones', function (Blueprint $table) {
            $table->string('altura', 10)->nullable()->change();
            $table->string('codigoPostal', 10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->string('altura', 10)->nullable(false)->change();
            $table->string('codigoPostal', 10)->nullable(false)->change();
        });
    }
};
