<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoReparacionSeeder extends Seeder
{
    public function run(): void
    {
        // Definimos los estados con sus IDs para asegurar consistencia
        $estados = [
            ['estadoReparacionID' => 1, 'nombreEstado' => 'Recibido', 'descripcion' => 'Equipo ingresado, pendiente de revisión.'],
            ['estadoReparacionID' => 2, 'nombreEstado' => 'Diagnóstico', 'descripcion' => 'Técnico evaluando el equipo.'],
            ['estadoReparacionID' => 3, 'nombreEstado' => 'Presupuestado', 'descripcion' => 'Esperando aprobación del cliente.'],
            ['estadoReparacionID' => 4, 'nombreEstado' => 'En Reparación', 'descripcion' => 'Reparación en curso.'],
            ['estadoReparacionID' => 5, 'nombreEstado' => 'Espera de Repuesto', 'descripcion' => 'Pausado por falta de repuesto.'],
            ['estadoReparacionID' => 6, 'nombreEstado' => 'Reparado', 'descripcion' => 'Listo para retirar.'],
            ['estadoReparacionID' => 7, 'nombreEstado' => 'Entregado', 'descripcion' => 'Finalizado y entregado.'],
            ['estadoReparacionID' => 8, 'nombreEstado' => 'Anulado', 'descripcion' => 'Cancelado sin reparación.'],
        ];

        foreach ($estados as $estado) {
            // Usamos updateOrInsert para que NO falle si ya existen
            DB::table('estados_reparacion')->updateOrInsert(
                ['estadoReparacionID' => $estado['estadoReparacionID']], 
                [
                    'nombreEstado' => $estado['nombreEstado'],
                    'descripcion' => $estado['descripcion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}