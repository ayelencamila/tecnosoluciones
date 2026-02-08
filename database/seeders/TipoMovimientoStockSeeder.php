<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoMovimientoStockSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            // Entradas
            ['nombre' => 'Entrada (Compra)', 'signo' => 1],
            ['nombre' => 'Ajuste Positivo', 'signo' => 1],
            ['nombre' => 'Devolución (Entrada)', 'signo' => 1],
            // Salidas
            ['nombre' => 'Salida (Venta)', 'signo' => -1],
            ['nombre' => 'Ajuste Negativo', 'signo' => -1],
            ['nombre' => 'Merma/Pérdida', 'signo' => -1],
            ['nombre' => 'Robo', 'signo' => -1],
            ['nombre' => 'Producto Defectuoso', 'signo' => -1],
            ['nombre' => 'Muestra/Donación', 'signo' => -1],
            ['nombre' => 'Uso Interno', 'signo' => -1],
        ];

        foreach ($tipos as $t) {
            DB::table('tipos_movimiento_stock')->updateOrInsert(['nombre' => $t['nombre']], $t);
        }
    }
}
