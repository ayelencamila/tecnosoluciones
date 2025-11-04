<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaProducto;

class CategoriaProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Equipos',
                'descripcion' => 'Equipos informáticos completos (notebooks, PCs, servidores, etc.)',
                'activo' => true,
            ],
            [
                'nombre' => 'Accesorios',
                'descripcion' => 'Periféricos y accesorios (teclados, mouse, monitores, etc.)',
                'activo' => true,
            ],
            [
                'nombre' => 'Repuestos',
                'descripcion' => 'Componentes y repuestos de hardware (RAM, discos, pantallas, etc.)',
                'activo' => true,
            ],
            [
                'nombre' => 'Servicios Técnicos',
                'descripcion' => 'Servicios de reparación, mantenimiento y soporte técnico',
                'activo' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaProducto::create($categoria);
        }
    }
}
