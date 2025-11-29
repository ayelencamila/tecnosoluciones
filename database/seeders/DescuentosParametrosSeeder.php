<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TipoDescuento;
use App\Models\AplicabilidadDescuento;

class DescuentosParametrosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tipos de Descuento
        // Usamos firstOrCreate para evitar duplicados si se corre varias veces
        TipoDescuento::firstOrCreate(['codigo' => 'PORCENTAJE'], ['nombre' => 'Porcentaje', 'activo' => true]);
        TipoDescuento::firstOrCreate(['codigo' => 'FIJO'], ['nombre' => 'Monto Fijo', 'activo' => true]);

        // 2. Aplicabilidades
        AplicabilidadDescuento::firstOrCreate(['codigo' => 'GLOBAL'], ['nombre' => 'Global (Total Venta)', 'activo' => true]);
        AplicabilidadDescuento::firstOrCreate(['codigo' => 'ITEM'], ['nombre' => 'Por Ítem (Producto)', 'activo' => true]);
        AplicabilidadDescuento::firstOrCreate(['codigo' => 'AMBOS'], ['nombre' => 'Flexible (Ambos)', 'activo' => true]);
    }
}