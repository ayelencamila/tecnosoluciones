<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\TipoCliente;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener IDs necesarios
        $categorias = CategoriaProducto::all();
        $estadoActivo = EstadoProducto::where('nombre', 'Activo')->first();
        $tiposCliente = TipoCliente::all();

        // Productos de ejemplo
        $productos = [
            // EQUIPOS
            [
                'codigo' => 'EQ-001',
                'nombre' => 'Notebook HP Pavilion 15',
                'descripcion' => 'Notebook 15.6" Intel Core i5, 8GB RAM, 256GB SSD',
                'marca' => 'HP',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Equipos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 5,
                'stockMinimo' => 2,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 850000],
                    ['tipo' => 'Mayorista', 'precio' => 780000],
                    ['tipo' => 'VIP', 'precio' => 750000],
                ],
            ],
            [
                'codigo' => 'EQ-002',
                'nombre' => 'PC Escritorio Intel i7',
                'descripcion' => 'PC completa Intel Core i7, 16GB RAM, 512GB SSD, Monitor 24"',
                'marca' => 'Ensamblado',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Equipos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 3,
                'stockMinimo' => 1,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 1200000],
                    ['tipo' => 'Mayorista', 'precio' => 1100000],
                    ['tipo' => 'VIP', 'precio' => 1050000],
                ],
            ],

            // ACCESORIOS
            [
                'codigo' => 'ACC-001',
                'nombre' => 'Teclado Mecánico RGB',
                'descripcion' => 'Teclado mecánico gaming con iluminación RGB',
                'marca' => 'Redragon',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Accesorios')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 15,
                'stockMinimo' => 5,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 35000],
                    ['tipo' => 'Mayorista', 'precio' => 30000],
                    ['tipo' => 'VIP', 'precio' => 28000],
                ],
            ],
            [
                'codigo' => 'ACC-002',
                'nombre' => 'Mouse Inalámbrico Logitech',
                'descripcion' => 'Mouse óptico inalámbrico 1600 DPI',
                'marca' => 'Logitech',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Accesorios')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 20,
                'stockMinimo' => 8,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 15000],
                    ['tipo' => 'Mayorista', 'precio' => 12000],
                    ['tipo' => 'VIP', 'precio' => 11000],
                ],
            ],
            [
                'codigo' => 'ACC-003',
                'nombre' => 'Monitor LED 24" Samsung',
                'descripcion' => 'Monitor Full HD 24 pulgadas, HDMI',
                'marca' => 'Samsung',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Accesorios')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 8,
                'stockMinimo' => 3,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 180000],
                    ['tipo' => 'Mayorista', 'precio' => 165000],
                    ['tipo' => 'VIP', 'precio' => 160000],
                ],
            ],

            // REPUESTOS
            [
                'codigo' => 'REP-001',
                'nombre' => 'Memoria RAM DDR4 8GB',
                'descripcion' => 'Módulo RAM DDR4 8GB 2666MHz',
                'marca' => 'Kingston',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Repuestos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 25,
                'stockMinimo' => 10,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 45000],
                    ['tipo' => 'Mayorista', 'precio' => 40000],
                    ['tipo' => 'VIP', 'precio' => 38000],
                ],
            ],
            [
                'codigo' => 'REP-002',
                'nombre' => 'Disco SSD 480GB',
                'descripcion' => 'Disco de estado sólido 480GB SATA III',
                'marca' => 'Kingston',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Repuestos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 12,
                'stockMinimo' => 5,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 55000],
                    ['tipo' => 'Mayorista', 'precio' => 50000],
                    ['tipo' => 'VIP', 'precio' => 48000],
                ],
            ],
            [
                'codigo' => 'REP-003',
                'nombre' => 'Fuente de Poder 600W',
                'descripcion' => 'Fuente ATX 600W 80+ Bronze',
                'marca' => 'Thermaltake',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Repuestos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 1,
                'stockMinimo' => 1,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 65000],
                    ['tipo' => 'Mayorista', 'precio' => 58000],
                    ['tipo' => 'VIP', 'precio' => 55000],
                ],
            ],

            // SERVICIOS TÉCNICOS
            [
                'codigo' => 'SERV-001',
                'nombre' => 'Servicio de Mantenimiento Preventivo',
                'descripcion' => 'Limpieza completa, renovación de pasta térmica, optimización de software',
                'marca' => null,
                'unidadMedida' => 'SERVICIO',
                'categoriaProductoID' => $categorias->where('nombre', 'Servicios Técnicos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'stockActual' => 999,
                'stockMinimo' => 1,
                'precios' => [
                    ['tipo' => 'Normal', 'precio' => 25000],
                    ['tipo' => 'Mayorista', 'precio' => 22000],
                    ['tipo' => 'VIP', 'precio' => 20000],
                ],
            ],
        ];

        foreach ($productos as $prodData) {
            $precios = $prodData['precios'];
            unset($prodData['precios']);

            // Crear producto
            $producto = Producto::create($prodData);

            // Crear precios para cada tipo de cliente
            foreach ($precios as $precioData) {
                $tipoCliente = $tiposCliente->where('nombreTipo', $precioData['tipo'])->first();

                if ($tipoCliente) {
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $tipoCliente->tipoClienteID,
                        'precio' => $precioData['precio'],
                        'fechaDesde' => now(),
                        'fechaHasta' => null, // Precio vigente
                    ]);
                }
            }
        }
    }
}
