<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoVentaSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['estadoVentaID' => 1, 'nombreEstado' => 'Pendiente'],   // Presupuesto o impaga
            ['estadoVentaID' => 2, 'nombreEstado' => 'Completada'],  // Pagada y entregada
            ['estadoVentaID' => 3, 'nombreEstado' => 'Anulada'],     // Cancelada
        ];

        foreach ($estados as $estado) {
            DB::table('estados_venta')->updateOrInsert(
                ['estadoVentaID' => $estado['estadoVentaID']],
                $estado
            );
        }
    }
}
