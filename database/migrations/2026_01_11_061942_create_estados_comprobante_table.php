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
        Schema::create('estados_comprobante', function (Blueprint $table) {
            $table->id('estado_id');
            $table->string('nombre', 50)->unique()->comment('EMITIDO, ANULADO, REEMPLAZADO');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar estados iniciales
        DB::table('estados_comprobante')->insert([
            ['nombre' => 'EMITIDO', 'descripcion' => 'Comprobante emitido y válido', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'ANULADO', 'descripcion' => 'Comprobante anulado', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'REEMPLAZADO', 'descripcion' => 'Comprobante reemplazado por otro', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_comprobante');
    }
};
