<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstadoProducto;

class EstadoProductoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'nombre' => 'Activo',
                'descripcion' => 'Producto disponible para la venta',
            ],
            [
                'nombre' => 'Inactivo',
                'descripcion' => 'Producto temporalmente no disponible',
            ],
            [
                'nombre' => 'Descontinuado',
                'descripcion' => 'Producto que ya no se comercializa',
            ],
        ];

        foreach ($estados as $estado) {
            EstadoProducto::create($estado);
        }
    }
}
