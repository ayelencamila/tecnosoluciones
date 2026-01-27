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
        Schema::create('tipos_comprobante', function (Blueprint $table) {
            $table->id('tipo_id');
            $table->string('codigo', 20)->unique()->comment('TICKET, FACTURA_A, etc.');
            $table->string('nombre', 100)->comment('Nombre descriptivo');
            $table->string('descripcion', 255)->nullable()->comment('Descripción del uso del comprobante');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo');
        });

        // Insertar tipos iniciales (INTERNOS - No fiscales)
        DB::table('tipos_comprobante')->insert([
            ['codigo' => 'TICKET', 'nombre' => 'Ticket', 'descripcion' => 'Comprobante interno de venta sin valor fiscal', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'FACTURA_A', 'nombre' => 'Factura A (Interna)', 'descripcion' => 'Documento interno de gestión - NO es factura fiscal', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'FACTURA_B', 'nombre' => 'Factura B (Interna)', 'descripcion' => 'Documento interno de gestión - NO es factura fiscal', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ORDEN_SERVICIO', 'nombre' => 'Orden de Servicio', 'descripcion' => 'Orden de trabajo de reparación', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'RECIBO_PAGO', 'nombre' => 'Recibo de Pago', 'descripcion' => 'Comprobante de pago recibido', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ORDEN_COMPRA', 'nombre' => 'Orden de Compra', 'descripcion' => 'Orden de compra a proveedor', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_comprobante');
    }
};
