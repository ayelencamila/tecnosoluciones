<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Stock;
use App\Models\Deposito;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrarProductoService
{
    public function handle(array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Crear Producto (Con nombres de columna nuevos)
            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                
                // CORRECCIÓN: Usamos IDs
                'marca_id' => $data['marca_id'] ?? null,
                'unidad_medida_id' => $data['unidad_medida_id'],
                
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'proveedor_habitual_id' => $data['proveedor_habitual_id'] ?? null,
            ]);

            // 2. Registrar Precios
            // (Si 'precios' no viene en el request, esto fallará. Asumimos que viene validado)
            if (isset($data['precios'])) {
                foreach ($data['precios'] as $precioData) {
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => Carbon::now(),
                        'usuarioID' => $userId,
                    ]);
                }
            }

            // 3. Inicializar Stock
            $depositoPrincipal = Deposito::where('nombre', 'like', '%Principal%')->first() 
                              ?? Deposito::where('activo', true)->first();

            if ($depositoPrincipal) {
                Stock::create([
                    'productoID' => $producto->id,
                    'deposito_id' => $depositoPrincipal->deposito_id,
                    'cantidad_disponible' => 0, 
                    'stock_minimo' => $data['stock_minimo'] ?? 0, // Si viene del form
                ]);
            }
            
            return $producto;
        });
    }
}