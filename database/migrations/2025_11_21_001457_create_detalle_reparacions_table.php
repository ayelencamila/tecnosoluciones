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
        // Definimos la tabla en plural español correcto
        Schema::create('detalle_reparaciones', function (Blueprint $table) {
            $table->id();

            // Vinculación a la reparación padre
            $table->foreignId('reparacion_id')
                  ->constrained('reparaciones', 'reparacionID')
                  ->onDelete('cascade'); // Si se borra la reparación (raro), se borran sus detalles

            // Vinculación al repuesto (Producto)
            // Tabla de productos tiene la PK 'id'.
            $table->foreignId('producto_id')
                  ->constrained('productos') 
                  ->onDelete('restrict');

            $table->integer('cantidad');
            
            // Guardamos el precio al momento del uso para histórico
            $table->decimal('precio_unitario', 10, 2); 
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_reparaciones');
    }
};
