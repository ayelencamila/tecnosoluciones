<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('rol_id');
            $table->string('nombre', 50)->unique()->comment('admin, vendedor, tecnico');
            $table->string('descripcion', 255)->nullable();
            $table->json('permisos')->nullable()->comment('Para futuro RBAC');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar roles iniciales
        DB::table('roles')->insert([
            ['nombre' => 'admin', 'descripcion' => 'Administrador del sistema', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'vendedor', 'descripcion' => 'Vendedor', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'tecnico', 'descripcion' => 'Técnico de reparaciones', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
