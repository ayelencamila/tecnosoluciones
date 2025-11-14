<?php

namespace Database\Seeders;

use App\Models\TipoCliente;
use Illuminate\Database\Seeder;

/**
 * Seeder para poblar los tipos de cliente del sistema
 *
 * Este seeder crea los dos tipos de cliente permitidos:
 * - Mayorista: Para clientes que compran en grandes volúmenes
 * - Minorista: Para clientes que compran al por menor
 */
class TipoClienteSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de tipos de cliente
     */
    public function run(): void
    {
        $tiposCliente = [
            [
                'nombreTipo' => 'Mayorista',
                'descripcion' => 'Clientes que realizan compras en grandes volúmenes con descuentos especiales',
                'activo' => true,
            ],
            [
                'nombreTipo' => 'Minorista',
                'descripcion' => 'Clientes que realizan compras al por menor con precios regulares',
                'activo' => true,
            ],
        ];

        foreach ($tiposCliente as $tipo) {
            TipoCliente::firstOrCreate(
                ['nombreTipo' => $tipo['nombreTipo']], // Buscar por nombre
                $tipo // Crear con todos los datos si no existe
            );
        }

        $this->command->info('✅ Tipos de cliente creados: Mayorista y Minorista');
    }
}
