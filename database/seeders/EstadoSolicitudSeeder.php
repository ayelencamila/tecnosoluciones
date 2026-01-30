<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSolicitudSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Objetivo: Asegurar los estados para el CU-20 (Automático -> Pendiente de Revisión)
     */
    public function run(): void
    {
        $estados = [
            [
                'nombre' => 'Pendiente de Revisión', // EL NUEVO ESTADO CRÍTICO
                'descripcion' => 'Generada automáticamente o borrador manual. Requiere aprobación para enviarse.',
                'activo' => true,
                'requiere_gestion_ofertas' => true, // Permite que el admin la vea y edite en CU-21
                'orden' => 0 // Aparecerá primero
            ],
            [
                'nombre' => 'Abierta',
                'descripcion' => 'Solicitud aprobada y lista para recibir cotizaciones.',
                'activo' => true,
                'requiere_gestion_ofertas' => true,
                'orden' => 1
            ],
            [
                'nombre' => 'Enviada',
                'descripcion' => 'Solicitud enviada a proveedores (WhatsApp/Email).',
                'activo' => true,
                'requiere_gestion_ofertas' => true,
                'orden' => 2
            ],
            [
                'nombre' => 'Cerrada',
                'descripcion' => 'Proceso finalizado (Orden de compra generada o desestimada).',
                'activo' => true,
                'requiere_gestion_ofertas' => false,
                'orden' => 3
            ],
            [
                'nombre' => 'Vencida',
                'descripcion' => 'Expiró el tiempo de cotización.',
                'activo' => true,
                'requiere_gestion_ofertas' => false,
                'orden' => 4
            ],
            [
                'nombre' => 'Cancelada',
                'descripcion' => 'Anulada manualmente.',
                'activo' => true,
                'requiere_gestion_ofertas' => false,
                'orden' => 5
            ],
        ];

        foreach ($estados as $estado) {
            DB::table('estados_solicitud')->updateOrInsert(
                ['nombre' => $estado['nombre']], // Busca por nombre
                [
                    'descripcion' => $estado['descripcion'],
                    'activo' => $estado['activo'],
                    'requiere_gestion_ofertas' => $estado['requiere_gestion_ofertas'],
                    'orden' => $estado['orden'],
                    'updated_at' => now(),
                    // Solo inserta created_at si es nuevo registro
                ]
            );
        }
    }
}