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
        Schema::create('tipos_movimiento_cuenta_corriente', function (Blueprint $table) {
            $table->id('tipo_id');
            $table->string('nombre', 50)->unique()->comment('Debito, Credito');
            $table->string('descripcion', 255)->nullable();
            $table->integer('multiplicador')->comment('+1 para aumentar saldo, -1 para disminuir');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar tipos iniciales
        DB::table('tipos_movimiento_cuenta_corriente')->insert([
            ['nombre' => 'Debito', 'descripcion' => 'Aumenta la deuda del cliente', 'multiplicador' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Credito', 'descripcion' => 'Disminuye la deuda del cliente (pago)', 'multiplicador' => -1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_movimiento_cuenta_corriente');
    }
};
