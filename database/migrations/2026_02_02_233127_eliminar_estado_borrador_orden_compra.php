<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Eliminar el estado "Borrador" de órdenes de compra.
     * Las OC ahora se crean directamente en estado "Enviada".
     */
    public function up(): void
    {
        // 1. Obtener IDs de los estados
        $estadoBorrador = DB::table('estados_orden_compra')->where('nombre', 'Borrador')->first();
        $estadoEnviada = DB::table('estados_orden_compra')->where('nombre', 'Enviada')->first();

        if ($estadoBorrador && $estadoEnviada) {
            // 2. Migrar OC en Borrador a Enviada
            DB::table('ordenes_compra')
                ->where('estado_id', $estadoBorrador->id)
                ->update(['estado_id' => $estadoEnviada->id]);

            // 3. Eliminar el estado Borrador
            DB::table('estados_orden_compra')
                ->where('id', $estadoBorrador->id)
                ->delete();
        }

        // 4. Actualizar el orden de los estados restantes
        DB::table('estados_orden_compra')
            ->where('nombre', 'Enviada')
            ->update(['orden' => 1]);
    }

    /**
     * Recrear el estado Borrador (rollback)
     */
    public function down(): void
    {
        // Recrear estado Borrador
        DB::table('estados_orden_compra')->insert([
            'nombre' => 'Borrador',
            'descripcion' => 'Orden de compra creada pero no enviada',
            'activo' => true,
            'orden' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reajustar orden de Enviada
        DB::table('estados_orden_compra')
            ->where('nombre', 'Enviada')
            ->update(['orden' => 2]);
    }
};
