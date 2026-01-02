<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('venta_id');

            // FK Cliente: RESTRICT (No borrar cliente si tiene compras)
            $table->unsignedBigInteger('clienteID');
            $table->foreign('clienteID')
                  ->references('clienteID')->on('clientes')
                  ->onDelete('restrict');

            // FK Usuario (Vendedor): RESTRICT (Mantener auditoría histórica)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict');

            // FK Estado Venta: RESTRICT
            $table->foreignId('estado_venta_id')
                  ->constrained('estados_venta', 'estadoVentaID')
                  ->onDelete('restrict');
            
            // FK Medio de Pago: RESTRICT
            $table->foreignId('medio_pago_id')
                  ->constrained('medios_pago', 'medioPagoID')
                  ->onDelete('restrict');

            $table->string('numero_comprobante', 100)->unique();
            $table->dateTime('fecha_venta');
            
            // Totales
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_descuentos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            // Auditoría y Notas
            $table->text('motivo_anulacion')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
