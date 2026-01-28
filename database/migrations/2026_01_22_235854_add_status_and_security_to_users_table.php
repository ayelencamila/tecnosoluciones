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
       Schema::table('users', function (Blueprint $table) {
        // 'activo': Determina si el actor tiene permiso para entrar al sistema.
        // Por defecto true. Si se despide a un empleado, se pasa a false.
          $table->boolean('activo')->default(true)->after('rol_id'); 

        // 'bloqueado_hasta': Seguridad (RNF4). Si falla muchas veces, 
        // se llena con una fecha futura y el sistema le impide entrar hasta entonces.
           $table->timestamp('bloqueado_hasta')->nullable()->after('activo'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn(['activo', 'bloqueado_hasta']);
       });
    }
};
