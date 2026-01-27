<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU-23: Recepcionar Mercadería
 * 
 * Tabla principal para registrar recepciones de mercadería.
 * Vinculada a una Orden de Compra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones_mercaderia', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recepcion', 30)->unique(); // Ej: REC-20260115-001
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Quien recepciona
            $table->dateTime('fecha_recepcion');
            $table->text('observaciones')->nullable(); // Motivo/observación requerido por CU-23
            $table->enum('tipo', ['parcial', 'total']); // Tipo de recepción
            $table->timestamps();

            $table->index('orden_compra_id');
            $table->index('fecha_recepcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepciones_mercaderia');
    }
};
