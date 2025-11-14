<?php

namespace Database\Seeders;

use App\Models\EstadoCliente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder para poblar los estados de cliente del sistema
 * 
 * Este seeder crea los diferentes estados en los que puede estar un cliente:
 * - Activo: Cliente habilitado para realizar operaciones
 * - Inactivo: Cliente temporalmente deshabilitado
 * - Suspendido: Cliente con restricciones por incumplimientos
 * - Moroso: Cliente con deudas pendientes
 */
class EstadoClienteSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de estados de cliente
     */
    public function run(): void
    {
        $estadosCliente = [
            [
                'nombreEstado' => 'Activo',
                'descripcion' => 'Cliente habilitado para realizar todas las operaciones del sistema',
            ],
            [
                'nombreEstado' => 'Inactivo',
                'descripcion' => 'Cliente temporalmente deshabilitado, no puede realizar operaciones',
            ],
            [
                'nombreEstado' => 'Suspendido',
                'descripcion' => 'Cliente suspendido por incumplimientos o violaciones de términos',
            ],
            [
                'nombreEstado' => 'Moroso',
                'descripcion' => 'Cliente con deudas pendientes que superan los límites establecidos',
            ],
        ];

        foreach ($estadosCliente as $estado) {
            EstadoCliente::firstOrCreate(
                ['nombreEstado' => $estado['nombreEstado']], // Buscar por nombre
                $estado // Crear con todos los datos si no existe
            );
        }

        $this->command->info('✅ Estados de cliente creados: Activo, Inactivo, Suspendido, Moroso');
    }
}
