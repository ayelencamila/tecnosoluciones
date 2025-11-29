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
                $cantidadInicial = $data['cantidad_inicial'] ?? 0;

                $stock = Stock::create([
                    'productoID' => $producto->id,
                    'deposito_id' => $depositoPrincipal->deposito_id,
                    'cantidad_disponible' => $cantidadInicial,
                    'stock_minimo' => $data['stock_minimo'] ?? 0,
                ]);

                // Registrar movimiento de inventario inicial si corresponde
                if ($cantidadInicial > 0) {
                    // Buscamos un tipo de movimiento de "Entrada" o "Ajuste"
                    // Nota: Idealmente usar el modelo TipoMovimientoStock
                    $tipoEntrada = \App\Models\TipoMovimientoStock::where('signo', 1)->first(); 
                    
                    if ($tipoEntrada) {
                         \App\Models\MovimientoStock::create([
                            'productoID' => $producto->id,
                            'deposito_id' => $depositoPrincipal->deposito_id,
                            'tipo_movimiento_id' => $tipoEntrada->id,
                            'cantidad' => $cantidadInicial,
                            'signo' => 1,
                            'stockAnterior' => 0,
                            'stockNuevo' => $cantidadInicial,
                            'motivo' => 'Inventario Inicial al Crear Producto',
                            'user_id' => $userId,
                            'fecha_movimiento' => now(),
                        ]);
                    }
                }
            }
            
            return $producto;
        });
    }
}