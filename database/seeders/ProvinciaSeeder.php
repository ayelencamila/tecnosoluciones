<?php

namespace Database\Seeders;

use App\Models\Provincia;
use Illuminate\Database\Seeder;

/**
 * Seeder de Provincias de Argentina
 *
 * Carga las 24 provincias argentinas en la base de datos
 */
class ProvinciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📍 Cargando provincias argentinas...');

        $provincias = [
            ['provinciaID' => 1, 'nombre' => 'Buenos Aires'],
            ['provinciaID' => 2, 'nombre' => 'Catamarca'],
            ['provinciaID' => 3, 'nombre' => 'Chaco'],
            ['provinciaID' => 4, 'nombre' => 'Chubut'],
            ['provinciaID' => 5, 'nombre' => 'Córdoba'],
            ['provinciaID' => 6, 'nombre' => 'Corrientes'],
            ['provinciaID' => 7, 'nombre' => 'Entre Ríos'],
            ['provinciaID' => 8, 'nombre' => 'Formosa'],
            ['provinciaID' => 9, 'nombre' => 'Jujuy'],
            ['provinciaID' => 10, 'nombre' => 'La Pampa'],
            ['provinciaID' => 11, 'nombre' => 'La Rioja'],
            ['provinciaID' => 12, 'nombre' => 'Mendoza'],
            ['provinciaID' => 13, 'nombre' => 'Misiones'],
            ['provinciaID' => 14, 'nombre' => 'Neuquén'],
            ['provinciaID' => 15, 'nombre' => 'Río Negro'],
            ['provinciaID' => 16, 'nombre' => 'Salta'],
            ['provinciaID' => 17, 'nombre' => 'San Juan'],
            ['provinciaID' => 18, 'nombre' => 'San Luis'],
            ['provinciaID' => 19, 'nombre' => 'Santa Cruz'],
            ['provinciaID' => 20, 'nombre' => 'Santa Fe'],
            ['provinciaID' => 21, 'nombre' => 'Santiago del Estero'],
            ['provinciaID' => 22, 'nombre' => 'Tierra del Fuego'],
            ['provinciaID' => 23, 'nombre' => 'Tucumán'],
            ['provinciaID' => 24, 'nombre' => 'Ciudad Autónoma de Buenos Aires'],
        ];

        foreach ($provincias as $provincia) {
            Provincia::updateOrCreate(
                ['provinciaID' => $provincia['provinciaID']],
                $provincia
            );
        }

        $this->command->info('✅ '.count($provincias).' provincias creadas exitosamente');
    }
}
