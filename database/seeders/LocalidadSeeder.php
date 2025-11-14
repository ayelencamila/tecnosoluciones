<?php

namespace Database\Seeders;

use App\Models\Localidad;
use Illuminate\Database\Seeder;

/**
 * Seeder de Localidades de Argentina
 * 
 * Carga las principales localidades de cada provincia argentina
 */
class LocalidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏘️  Cargando localidades principales...');
        
        $localidades = [
            // Buenos Aires
            ['nombre' => 'La Plata', 'provinciaID' => 1],
            ['nombre' => 'Mar del Plata', 'provinciaID' => 1],
            ['nombre' => 'Bahía Blanca', 'provinciaID' => 1],
            ['nombre' => 'Tandil', 'provinciaID' => 1],
            ['nombre' => 'Olavarría', 'provinciaID' => 1],
            ['nombre' => 'Pergamino', 'provinciaID' => 1],
            ['nombre' => 'Luján', 'provinciaID' => 1],
            ['nombre' => 'Zárate', 'provinciaID' => 1],
            ['nombre' => 'Campana', 'provinciaID' => 1],
            ['nombre' => 'San Nicolás', 'provinciaID' => 1],
            
            // Catamarca
            ['nombre' => 'San Fernando del Valle de Catamarca', 'provinciaID' => 2],
            ['nombre' => 'Andalgalá', 'provinciaID' => 2],
            ['nombre' => 'Belén', 'provinciaID' => 2],
            ['nombre' => 'Santa María', 'provinciaID' => 2],
            
            // Chaco
            ['nombre' => 'Resistencia', 'provinciaID' => 3],
            ['nombre' => 'Presidencia Roque Sáenz Peña', 'provinciaID' => 3],
            ['nombre' => 'Villa Ángela', 'provinciaID' => 3],
            ['nombre' => 'Charata', 'provinciaID' => 3],
            
            // Chubut
            ['nombre' => 'Rawson', 'provinciaID' => 4],
            ['nombre' => 'Comodoro Rivadavia', 'provinciaID' => 4],
            ['nombre' => 'Puerto Madryn', 'provinciaID' => 4],
            ['nombre' => 'Trelew', 'provinciaID' => 4],
            ['nombre' => 'Esquel', 'provinciaID' => 4],
            
            // Córdoba
            ['nombre' => 'Córdoba', 'provinciaID' => 5],
            ['nombre' => 'Villa María', 'provinciaID' => 5],
            ['nombre' => 'Río Cuarto', 'provinciaID' => 5],
            ['nombre' => 'San Francisco', 'provinciaID' => 5],
            ['nombre' => 'Villa Carlos Paz', 'provinciaID' => 5],
            ['nombre' => 'Alta Gracia', 'provinciaID' => 5],
            
            // Corrientes
            ['nombre' => 'Corrientes', 'provinciaID' => 6],
            ['nombre' => 'Goya', 'provinciaID' => 6],
            ['nombre' => 'Paso de los Libres', 'provinciaID' => 6],
            ['nombre' => 'Curuzú Cuatiá', 'provinciaID' => 6],
            
            // Entre Ríos
            ['nombre' => 'Paraná', 'provinciaID' => 7],
            ['nombre' => 'Concordia', 'provinciaID' => 7],
            ['nombre' => 'Gualeguaychú', 'provinciaID' => 7],
            ['nombre' => 'Concepción del Uruguay', 'provinciaID' => 7],
            
            // Formosa
            ['nombre' => 'Formosa', 'provinciaID' => 8],
            ['nombre' => 'Clorinda', 'provinciaID' => 8],
            ['nombre' => 'Pirané', 'provinciaID' => 8],
            
            // Jujuy
            ['nombre' => 'San Salvador de Jujuy', 'provinciaID' => 9],
            ['nombre' => 'San Pedro de Jujuy', 'provinciaID' => 9],
            ['nombre' => 'Libertador General San Martín', 'provinciaID' => 9],
            ['nombre' => 'Palpala', 'provinciaID' => 9],
            
            // La Pampa
            ['nombre' => 'Santa Rosa', 'provinciaID' => 10],
            ['nombre' => 'General Pico', 'provinciaID' => 10],
            ['nombre' => 'General Acha', 'provinciaID' => 10],
            
            // La Rioja
            ['nombre' => 'La Rioja', 'provinciaID' => 11],
            ['nombre' => 'Chilecito', 'provinciaID' => 11],
            ['nombre' => 'Chamical', 'provinciaID' => 11],
            
            // Mendoza
            ['nombre' => 'Mendoza', 'provinciaID' => 12],
            ['nombre' => 'San Rafael', 'provinciaID' => 12],
            ['nombre' => 'Godoy Cruz', 'provinciaID' => 12],
            ['nombre' => 'Luján de Cuyo', 'provinciaID' => 12],
            ['nombre' => 'Maipú', 'provinciaID' => 12],
            
            // Misiones
            ['nombre' => 'Posadas', 'provinciaID' => 13],
            ['nombre' => 'Oberá', 'provinciaID' => 13],
            ['nombre' => 'Eldorado', 'provinciaID' => 13],
            ['nombre' => 'Puerto Iguazú', 'provinciaID' => 13],
            
            // Neuquén
            ['nombre' => 'Neuquén', 'provinciaID' => 14],
            ['nombre' => 'San Martín de los Andes', 'provinciaID' => 14],
            ['nombre' => 'Zapala', 'provinciaID' => 14],
            ['nombre' => 'Cutral Có', 'provinciaID' => 14],
            
            // Río Negro
            ['nombre' => 'Viedma', 'provinciaID' => 15],
            ['nombre' => 'San Carlos de Bariloche', 'provinciaID' => 15],
            ['nombre' => 'General Roca', 'provinciaID' => 15],
            ['nombre' => 'Cipolletti', 'provinciaID' => 15],
            
            // Salta
            ['nombre' => 'Salta', 'provinciaID' => 16],
            ['nombre' => 'Tartagal', 'provinciaID' => 16],
            ['nombre' => 'Orán', 'provinciaID' => 16],
            ['nombre' => 'Metán', 'provinciaID' => 16],
            
            // San Juan
            ['nombre' => 'San Juan', 'provinciaID' => 17],
            ['nombre' => 'Caucete', 'provinciaID' => 17],
            ['nombre' => 'Chimbas', 'provinciaID' => 17],
            
            // San Luis
            ['nombre' => 'San Luis', 'provinciaID' => 18],
            ['nombre' => 'Villa Mercedes', 'provinciaID' => 18],
            ['nombre' => 'Merlo', 'provinciaID' => 18],
            
            // Santa Cruz
            ['nombre' => 'Río Gallegos', 'provinciaID' => 19],
            ['nombre' => 'Caleta Olivia', 'provinciaID' => 19],
            ['nombre' => 'El Calafate', 'provinciaID' => 19],
            
            // Santa Fe
            ['nombre' => 'Santa Fe', 'provinciaID' => 20],
            ['nombre' => 'Rosario', 'provinciaID' => 20],
            ['nombre' => 'Rafaela', 'provinciaID' => 20],
            ['nombre' => 'Venado Tuerto', 'provinciaID' => 20],
            ['nombre' => 'Reconquista', 'provinciaID' => 20],
            
            // Santiago del Estero
            ['nombre' => 'Santiago del Estero', 'provinciaID' => 21],
            ['nombre' => 'La Banda', 'provinciaID' => 21],
            ['nombre' => 'Termas de Río Hondo', 'provinciaID' => 21],
            
            // Tierra del Fuego
            ['nombre' => 'Ushuaia', 'provinciaID' => 22],
            ['nombre' => 'Río Grande', 'provinciaID' => 22],
            ['nombre' => 'Tolhuin', 'provinciaID' => 22],
            
            // Tucumán
            ['nombre' => 'San Miguel de Tucumán', 'provinciaID' => 23],
            ['nombre' => 'Concepción', 'provinciaID' => 23],
            ['nombre' => 'Tafí Viejo', 'provinciaID' => 23],
            ['nombre' => 'Yerba Buena', 'provinciaID' => 23],
            
            // Ciudad Autónoma de Buenos Aires
            ['nombre' => 'CABA', 'provinciaID' => 24],
        ];

        foreach ($localidades as $localidad) {
            Localidad::create($localidad);
        }

        $this->command->info('✅ ' . count($localidades) . ' localidades creadas exitosamente');
    }
}
