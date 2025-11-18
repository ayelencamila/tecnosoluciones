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
        // Este bloque USA Schema::table para MODIFICAR
        Schema::table('productos', function (Blueprint $table) {
            
            $table->foreignId('proveedor_habitual_id')
                  ->nullable()
                  ->after('estadoProductoID') // La pone después de estadoProductoID
                  ->constrained('proveedores'); // Funciona porque 'proveedores' ya existe
            
            $table->index('proveedor_habitual_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_habitual_id']);
            $table->dropColumn('proveedor_habitual_id');
        });
    }
};