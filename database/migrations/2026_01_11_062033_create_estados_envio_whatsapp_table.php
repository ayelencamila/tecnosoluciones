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
        Schema::create('estados_envio_whatsapp', function (Blueprint $table) {
            $table->id('estado_id');
            $table->string('nombre', 50)->unique()->comment('Pendiente, Enviado, Fallido');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('es_final')->default(false)->comment('Si es estado final del envío');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        // Insertar estados iniciales
        DB::table('estados_envio_whatsapp')->insert([
            ['nombre' => 'Pendiente', 'descripcion' => 'Mensaje en cola de envío', 'es_final' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Enviado', 'descripcion' => 'Mensaje enviado exitosamente', 'es_final' => true, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Fallido', 'descripcion' => 'Fallo en el envío del mensaje', 'es_final' => true, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_envio_whatsapp');
    }
};
