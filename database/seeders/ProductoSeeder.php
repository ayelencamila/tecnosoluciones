<?php

namespace Database\Seeders;

// Importamos los modelos que usaremos
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\TipoCliente;
use App\Models\Deposito; // <-- IMPORTANTE
use App\Models\Stock;      // <-- IMPORTANTE
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // <-- IMPORTANTE

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener IDs de referencia
        $categorias = CategoriaProducto::all();
        $estadoActivo = EstadoProducto::where('nombre', 'Activo')->first();
        $tiposCliente = TipoCliente::all();
        
        // Buscamos el depósito principal (debe existir gracias al DepositoSeeder)
        $depositoPrincipal = Deposito::first();

        if (!$estadoActivo || !$depositoPrincipal || $tiposCliente->isEmpty() || $categorias->isEmpty()) {
            $this->command->error('Error: Faltan datos base (Estados, Depósitos, Tipos de Cliente o Categorías) para correr el ProductoSeeder.');
            return;
        }
        
        // Agrupamos la creación en una transacción
        DB::transaction(function () use ($categorias, $estadoActivo, $tiposCliente, $depositoPrincipal) {
            
            // Definimos los tipos de cliente para los precios
            $tipoNormal = $tiposCliente->where('nombreTipo', 'Minorista')->first()->tipoClienteID;
            $tipoMayorista = $tiposCliente->where('nombreTipo', 'Mayorista')->first()->tipoClienteID;

            // --- PRODUCTO FÍSICO 1 (EQUIPO) ---
            $prod1 = Producto::create([
                'codigo' => 'EQ-001',
                'nombre' => 'Notebook HP Pavilion 15',
                'descripcion' => 'Notebook 15.6" Intel Core i5, 8GB RAM, 256GB SSD',
                'marca' => 'HP',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Equipos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'proveedor_habitual_id' => null 
            ]);

            // Creamos su Stock en la tabla 'stock'
            Stock::create([
                'productoID' => $prod1->id,
                'deposito_id' => $depositoPrincipal->deposito_id,
                'cantidad_disponible' => 5, 
                'stock_minimo' => 2,        
            ]);

            // Creamos sus Precios
            PrecioProducto::create(['productoID' => $prod1->id, 'tipoClienteID' => $tipoNormal, 'precio' => 850000, 'fechaDesde' => now()]);
            PrecioProducto::create(['productoID' => $prod1->id, 'tipoClienteID' => $tipoMayorista, 'precio' => 780000, 'fechaDesde' => now()]);

            // --- PRODUCTO FÍSICO 2 (REPUESTO) ---
            $prod2 = Producto::create([
                'codigo' => 'REP-002',
                'nombre' => 'Disco SSD 480GB',
                'descripcion' => 'Disco de estado sólido 480GB SATA III',
                'marca' => 'Kingston',
                'unidadMedida' => 'UNIDAD',
                'categoriaProductoID' => $categorias->where('nombre', 'Repuestos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'proveedor_habitual_id' => null
            ]);
            
            // Creamos su Stock
            Stock::create([
                'productoID' => $prod2->id,
                'deposito_id' => $depositoPrincipal->deposito_id,
                'cantidad_disponible' => 12,
                'stock_minimo' => 5,
            ]);

            // Creamos sus Precios
            PrecioProducto::create(['productoID' => $prod2->id, 'tipoClienteID' => $tipoNormal, 'precio' => 55000, 'fechaDesde' => now()]);
            PrecioProducto::create(['productoID' => $prod2->id, 'tipoClienteID' => $tipoMayorista, 'precio' => 50000, 'fechaDesde' => now()]);

            // --- SERVICIO (NO FÍSICO) ---
            $serv1 = Producto::create([
                'codigo' => 'SERV-001',
                'nombre' => 'Servicio de Mantenimiento Preventivo',
                'descripcion' => 'Limpieza completa, renovación de pasta térmica, optimización',
                'marca' => null,
                'unidadMedida' => 'SERVICIO',
                'categoriaProductoID' => $categorias->where('nombre', 'Servicios Técnicos')->first()->id,
                'estadoProductoID' => $estadoActivo->id,
                'proveedor_habitual_id' => null
            ]);
            
            // ¡NO CREAMOS REGISTRO DE STOCK! (Porque es un servicio)

            // Creamos sus Precios
            PrecioProducto::create(['productoID' => $serv1->id, 'tipoClienteID' => $tipoNormal, 'precio' => 25000, 'fechaDesde' => now()]);
            PrecioProducto::create(['productoID' => $serv1->id, 'tipoClienteID' => $tipoMayorista, 'precio' => 22000, 'fechaDesde' => now()]);
            
            $this->command->info('Productos y stocks de ejemplo creados exitosamente.');
        
        });
    }
}