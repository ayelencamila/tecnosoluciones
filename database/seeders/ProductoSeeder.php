<?php

namespace Database\Seeders;

// Importamos los modelos que usaremos
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\TipoCliente;
use App\Models\Deposito;
use App\Models\Stock;
use App\Models\Marca;        // <--- NUEVO: Necesario para crear/buscar marcas
use App\Models\UnidadMedida; // <--- NUEVO: Necesario para unidades
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener IDs de referencia existentes
        $categorias = CategoriaProducto::all();
        $estadoActivo = EstadoProducto::where('nombre', 'Activo')->first();
        $tiposCliente = TipoCliente::all();
        
        // Buscamos el depósito principal
        $depositoPrincipal = Deposito::first();

        if (!$estadoActivo || !$depositoPrincipal || $tiposCliente->isEmpty() || $categorias->isEmpty()) {
            $this->command->error('Error: Faltan datos base (Estados, Depósitos, Tipos de Cliente o Categorías) para correr el ProductoSeeder.');
            return;
        }
        
        // --- PREPARACIÓN DE DATOS MAESTROS (Misión 6) ---
        // Aseguramos que existan las Marcas y Unidades necesarias para estos productos de prueba
        
        $marcaHP = Marca::firstOrCreate(['nombre' => 'HP'], ['activo' => true]);
        $marcaKingston = Marca::firstOrCreate(['nombre' => 'Kingston'], ['activo' => true]);
        
        $unidadU = UnidadMedida::firstOrCreate(['abreviatura' => 'u'], ['nombre' => 'Unidad', 'activo' => true]);
        $unidadSrv = UnidadMedida::firstOrCreate(['abreviatura' => 'srv'], ['nombre' => 'Servicio', 'activo' => true]);


        // Agrupamos la creación en una transacción
        DB::transaction(function () use ($categorias, $estadoActivo, $tiposCliente, $depositoPrincipal, $marcaHP, $marcaKingston, $unidadU, $unidadSrv) {
            
            $tipoNormal = $tiposCliente->where('nombreTipo', 'Minorista')->first()->tipoClienteID;
            $tipoMayorista = $tiposCliente->where('nombreTipo', 'Mayorista')->first()->tipoClienteID;

            // --- PRODUCTO FÍSICO 1 (EQUIPO) ---
            $prod1 = Producto::firstOrCreate(
                ['codigo' => 'EQ-001'],
                [
                    'nombre' => 'Notebook HP Pavilion 15',
                    'descripcion' => 'Notebook 15.6" Intel Core i5, 8GB RAM, 256GB SSD',
                    
                    // CAMBIOS PARA SOPORTAR LA NUEVA ESTRUCTURA:
                    'marca_id' => $marcaHP->id,           // Usamos el ID de la marca
                    'unidad_medida_id' => $unidadU->id,   // Usamos el ID de la unidad
                    
                    'categoriaProductoID' => $categorias->where('nombre', 'Equipos')->first()->id,
                    'estadoProductoID' => $estadoActivo->id,
                    'proveedor_habitual_id' => null 
                ]
            );

            // Stock
            Stock::firstOrCreate(
                ['productoID' => $prod1->id, 'deposito_id' => $depositoPrincipal->deposito_id],
                [
                    'cantidad_disponible' => 5, 
                    'stock_minimo' => 2,        
                ]
            );

            // Precios
            PrecioProducto::firstOrCreate(
                ['productoID' => $prod1->id, 'tipoClienteID' => $tipoNormal],
                ['precio' => 850000, 'fechaDesde' => now()]
            );
            PrecioProducto::firstOrCreate(
                ['productoID' => $prod1->id, 'tipoClienteID' => $tipoMayorista],
                ['precio' => 780000, 'fechaDesde' => now()]
            );

            // --- PRODUCTO FÍSICO 2 (REPUESTO) ---
            $prod2 = Producto::firstOrCreate(
                ['codigo' => 'REP-002'],
                [
                    'nombre' => 'Disco SSD 480GB',
                    'descripcion' => 'Disco de estado sólido 480GB SATA III',
                    
                    // CAMBIOS:
                    'marca_id' => $marcaKingston->id,
                    'unidad_medida_id' => $unidadU->id,
                    
                    'categoriaProductoID' => $categorias->where('nombre', 'Repuestos')->first()->id,
                    'estadoProductoID' => $estadoActivo->id,
                    'proveedor_habitual_id' => null
                ]
            );
            
            // Stock
            Stock::firstOrCreate(
                ['productoID' => $prod2->id, 'deposito_id' => $depositoPrincipal->deposito_id],
                [
                    'cantidad_disponible' => 12,
                    'stock_minimo' => 5,
                ]
            );

            // Precios
            PrecioProducto::firstOrCreate(
                ['productoID' => $prod2->id, 'tipoClienteID' => $tipoNormal],
                ['precio' => 55000, 'fechaDesde' => now()]
            );
            PrecioProducto::firstOrCreate(
                ['productoID' => $prod2->id, 'tipoClienteID' => $tipoMayorista],
                ['precio' => 50000, 'fechaDesde' => now()]
            );

            // --- SERVICIO (NO FÍSICO) ---
            $serv1 = Producto::firstOrCreate(
                ['codigo' => 'SERV-001'],
                [
                    'nombre' => 'Servicio de Mantenimiento Preventivo',
                    'descripcion' => 'Limpieza completa, renovación de pasta térmica, optimización',
                    
                    // CAMBIOS:
                    'marca_id' => null, // Servicios sin marca
                    'unidad_medida_id' => $unidadSrv->id,
                
                'categoriaProductoID' => $categorias->where('nombre', 'Servicios Técnicos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'proveedor_habitual_id' => null
            ]);
            
            // Precios
            PrecioProducto::firstOrCreate(
                ['productoID' => $serv1->id, 'tipoClienteID' => $tipoNormal],
                ['precio' => 25000, 'fechaDesde' => now()]
            );
            PrecioProducto::firstOrCreate(
                ['productoID' => $serv1->id, 'tipoClienteID' => $tipoMayorista],
                ['precio' => 22000, 'fechaDesde' => now()]
            );
            
            $this->command->info('Productos y stocks de ejemplo creados exitosamente (Actualizado Misión 6).');
        
        });
    }
}