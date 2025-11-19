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
    /**
     * Ejecuta CU-25: Registrar Producto
     */
    public function handle(array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Crear Producto (Con Proveedor Habitual)
            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'proveedor_habitual_id' => $data['proveedor_habitual_id'] ?? null, // CU-25
            ]);

            // 2. Registrar Precios (Minorista/Mayorista)
            foreach ($data['precios'] as $precioData) {
                PrecioProducto::create([
                    'productoID' => $producto->id,
                    'tipoClienteID' => $precioData['tipoClienteID'],
                    'precio' => $precioData['precio'],
                    'fechaDesde' => Carbon::now(),
                    'usuarioID' => $userId,
                ]);
            }

            // 3. Inicializar Stock (Tabla Stock)
            // Se crea con cantidad 0 en el depósito principal.
            $depositoPrincipal = Deposito::where('esPrincipal', true)->first() 
                              ?? Deposito::where('activo', true)->first();

            if ($depositoPrincipal) {
                Stock::create([
                    'productoID' => $producto->id,
                    'deposito_id' => $depositoPrincipal->deposito_id,
                    'cantidad_disponible' => 0, 
                    'stock_minimo' => $data['stock_minimo'] ?? 0,
                ]);
            }

            // La auditoría de creación puede ir acá o en un Observer
            
            return $producto;
        });
    }
}