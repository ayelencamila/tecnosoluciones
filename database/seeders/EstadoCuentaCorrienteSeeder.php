<?php

namespace Database\Seeders;

use App\Models\EstadoCuentaCorriente;
use Illuminate\Database\Seeder;

/**
 * Seeder para poblar los estados de cuenta corriente del sistema
 * * Este seeder crea los diferentes estados en los que puede estar una cuenta corriente:
 * - Activa: Cuenta operativa con transacciones habilitadas
 * - Bloqueada: Cuenta temporalmente bloqueada por seguridad
 * - Vencida: Cuenta con pagos vencidos que requieren atención
 * - Cerrada: Cuenta definitivamente cerrada
 * - Pendiente de Aprobación: Cuenta creada pero requiere revisión antes de operar
 */
class EstadoCuentaCorrienteSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de estados de cuenta corriente
     */
    public function run(): void
    {
        $estadosCuentaCorriente = [
            [
                'nombreEstado' => 'Activa',
                'descripcion' => 'Cuenta corriente operativa con todas las transacciones habilitadas',
            ],
            [
                'nombreEstado' => 'Bloqueada',
                'descripcion' => 'Cuenta temporalmente bloqueada por motivos de seguridad o administrativos',
            ],
            [
                'nombreEstado' => 'Vencida',
                'descripcion' => 'Cuenta con pagos vencidos que requieren regularización inmediata',
            ],
            [
                'nombreEstado' => 'Cerrada',
                'descripcion' => 'Cuenta definitivamente cerrada, sin posibilidad de nuevas transacciones',
            ],
            [ // <--- AGREGAR ESTE NUEVO ESTADO
                'nombreEstado' => 'Pendiente de Aprobación',
                'descripcion' => 'Cuenta corriente creada y a la espera de ser aprobada por un administrador antes de su uso.',
            ],
        ];

        foreach ($estadosCuentaCorriente as $estado) {
            EstadoCuentaCorriente::firstOrCreate(
                ['nombreEstado' => $estado['nombreEstado']], // Buscar por nombre
                $estado // Crear con todos los datos si no existe
            );
        }

        $this->command->info('✅ Estados de cuenta corriente creados: Activa, Bloqueada, Vencida, Cerrada, Pendiente de Aprobación');
    }
}
