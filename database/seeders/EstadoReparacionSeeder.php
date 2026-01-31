<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoReparacionSeeder extends Seeder
{
    /**
     * Estados de reparación FIJOS para el sistema.
     * 
     * IMPORTANTE: Estos estados NO son parametrizables por el usuario.
     * El flujo fue simplificado según requerimiento del cliente:
     * - El presupuesto se da al momento del ingreso
     * - No se requiere esperar aprobación del cliente
     * 
     * Flujo simplificado:
     * Recibido → En Reparación → Reparado → Entregado
     *                ↓
     *        Espera de Repuesto
     *                ↓
     *            Anulado (desde cualquier estado no final)
     * 
     * Estados que PAUSAN el SLA (CU-14):
     * - Espera de Repuesto (ID 3)
     * - Reparado (ID 4) - Listo para retiro, responsabilidad del cliente
     */
    public function run(): void
    {
        // Estados FIJOS del sistema - NO modificar IDs
        $estados = [
            ['estadoReparacionID' => 1, 'nombreEstado' => 'Recibido', 'descripcion' => 'Equipo ingresado con presupuesto. Pendiente de reparación.'],
            ['estadoReparacionID' => 2, 'nombreEstado' => 'En Reparación', 'descripcion' => 'Reparación en curso por el técnico.'],
            ['estadoReparacionID' => 3, 'nombreEstado' => 'Espera de Repuesto', 'descripcion' => 'Pausado aguardando repuesto de proveedor. [Pausa SLA]'],
            ['estadoReparacionID' => 4, 'nombreEstado' => 'Reparado', 'descripcion' => 'Listo para retirar por el cliente. [Pausa SLA]'],
            ['estadoReparacionID' => 5, 'nombreEstado' => 'Entregado', 'descripcion' => 'Finalizado y entregado al cliente.'],
            ['estadoReparacionID' => 6, 'nombreEstado' => 'Anulado', 'descripcion' => 'Cancelado sin reparación.'],
        ];

        foreach ($estados as $estado) {
            // Usamos updateOrInsert para que NO falle si ya existen
            DB::table('estados_reparacion')->updateOrInsert(
                ['estadoReparacionID' => $estado['estadoReparacionID']], 
                [
                    'nombreEstado' => $estado['nombreEstado'],
                    'descripcion' => $estado['descripcion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Eliminar estados obsoletos si existen (IDs 7 y 8 del esquema anterior)
        DB::table('estados_reparacion')
            ->whereIn('estadoReparacionID', [7, 8])
            ->whereNotIn('nombreEstado', ['Entregado', 'Anulado']) // Protección
            ->delete();
            
        $this->command->info('✓ 6 estados de reparación configurados (flujo simplificado)');
    }
}