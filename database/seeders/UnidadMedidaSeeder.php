<?php

namespace Database\Seeders;

use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

class UnidadMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['nombre' => 'Unidad', 'abreviatura' => 'u'],
            ['nombre' => 'Servicio', 'abreviatura' => 'srv'], 
            ['nombre' => 'Metro', 'abreviatura' => 'm'],
            ['nombre' => 'Litro', 'abreviatura' => 'l'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'Pack', 'abreviatura' => 'pck'],
        ];

        foreach ($unidades as $u) {
            UnidadMedida::firstOrCreate(['abreviatura' => $u['abreviatura']], $u);
        }
    }
}
