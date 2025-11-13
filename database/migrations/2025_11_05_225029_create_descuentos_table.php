<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id('descuento_id'); // Tu PK
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('descripcion');

            $table->enum('tipo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 15, 2);

            // *** Ojo al piojo: Adición clave para la Opción B ***
            // Le dice al sistema dónde se puede aplicar este descuento
            $table->enum('aplicabilidad', ['total', 'item', 'ambos'])->default('total');

            $table->boolean('activo')->default(true);
            $table->date('valido_desde')->nullable();
            $table->date('valido_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
