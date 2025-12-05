<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MotivosDemoraReparacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motivos = [
            [
                'codigo' => 'FALTA_REPUESTO',
                'nombre' => 'Falta de repuesto',
                'descripcion' => 'El repuesto necesario no está disponible en stock',
                'requiere_bonificacion' => true,
                'pausa_sla' => true, // Pausa el conteo porque depende de proveedor externo
                'activo' => true,
                'orden' => 1,
            ],
            [
                'codigo' => 'ALTA_COMPLEJIDAD',
                'nombre' => 'Reparación de alta complejidad',
                'descripcion' => 'La reparación requiere más tiempo del estimado inicialmente',
                'requiere_bonificacion' => true,
                'pausa_sla' => false,
                'activo' => true,
                'orden' => 2,
            ],
            [
                'codigo' => 'ESPERA_APROBACION',
                'nombre' => 'Espera aprobación del cliente',
                'descripcion' => 'Se informó presupuesto al cliente y está en espera de confirmación',
                'requiere_bonificacion' => false, // No califica porque la demora es del cliente
                'pausa_sla' => true,
                'activo' => true,
                'orden' => 3,
            ],
            [
                'codigo' => 'FALLA_DIAGNOSTICO',
                'nombre' => 'Falla en diagnóstico inicial',
                'descripcion' => 'Se encontraron fallas adicionales durante la reparación',
                'requiere_bonificacion' => true,
                'pausa_sla' => false,
                'activo' => true,
                'orden' => 4,
            ],
            [
                'codigo' => 'SOBRECARGA_TRABAJO',
                'nombre' => 'Sobrecarga de trabajo del técnico',
                'descripcion' => 'Volumen alto de reparaciones retrasó el proceso',
                'requiere_bonificacion' => true,
                'pausa_sla' => false,
                'activo' => true,
                'orden' => 5,
            ],
            [
                'codigo' => 'EQUIPO_ESPECIALIZADO',
                'nombre' => 'Requiere equipo especializado externo',
                'descripcion' => 'La reparación requiere herramientas o equipos que no están disponibles',
                'requiere_bonificacion' => true,
                'pausa_sla' => true,
                'activo' => true,
                'orden' => 6,
            ],
            [
                'codigo' => 'CLIENTE_NO_CONTACTABLE',
                'nombre' => 'Cliente no contactable',
                'descripcion' => 'No se pudo contactar al cliente para consultas necesarias',
                'requiere_bonificacion' => false,
                'pausa_sla' => true,
                'activo' => true,
                'orden' => 7,
            ],
            [
                'codigo' => 'OTROS',
                'nombre' => 'Otros motivos',
                'descripcion' => 'Motivo de demora no contemplado en las categorías anteriores',
                'requiere_bonificacion' => true,
                'pausa_sla' => false,
                'activo' => true,
                'orden' => 99,
            ],
        ];

        foreach ($motivos as $motivo) {
            \App\Models\MotivoDemoraReparacion::create($motivo);
        }

        $this->command->info('✓ 8 motivos de demora creados exitosamente');
    }
}
