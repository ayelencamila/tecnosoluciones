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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id('gasto_id');
            $table->unsignedBigInteger('categoria_gasto_id');
            $table->date('fecha');
            $table->string('descripcion', 255);
            $table->decimal('monto', 12, 2);
            $table->string('comprobante', 100)->nullable(); // Número de factura/recibo
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('usuario_id'); // Quién registró
            $table->boolean('anulado')->default(false);
            $table->timestamps();

            $table->foreign('categoria_gasto_id')->references('categoria_gasto_id')->on('categorias_gasto');
            $table->foreign('usuario_id')->references('id')->on('users');
            
            $table->index(['fecha', 'anulado']);
            $table->index('categoria_gasto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
