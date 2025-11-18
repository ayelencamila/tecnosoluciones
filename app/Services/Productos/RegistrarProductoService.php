<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use App\Models\Stock;
use App\Models\Deposito;
use App\Models\CategoriaProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarProductoService
{
    /**
     * Registra un nuevo producto, sus precios iniciales y su stock inicial (CU-25).
     *
     * @param array $data Los datos validados por StoreProductoRequest
     * @param int $userId El ID del usuario que realiza la acción
     * @return Producto El producto creado
     */
    public function handle(array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Crear el Producto (Catálogo)
            // Cumple con la migración: no incluye stock, pero sí proveedor_habitual_id
            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'proveedor_habitual_id' => $data['proveedor_habitual_id'] ?? null,
            ]);

            // 2. Crear Precios Iniciales (CU-25, Paso 4)
            $fechaActual = now()->toDateString();
            
            foreach ($data['precios'] as $precioData) {
                PrecioProducto::create([
                    'productoID' => $producto->id,
                    'tipoClienteID' => $precioData['tipoClienteID'],
                    'precio' => $precioData['precio'],
                    'fechaDesde' => $fechaActual,
                    'fechaHasta' => null, // Vigente
                ]);
            }

            // 3. Crear Stock Inicial (Poscondición CU-25 y CU-29)
            // Verificamos si es un "Servicio" para no crearle stock
            $categoria = CategoriaProducto::find($data['categoriaProductoID']);
            
            // Si la categoría NO es "Servicios Técnicos", creamos su registro de stock
            if ($categoria && $categoria->nombre !== 'Servicios Técnicos') {
                
                // Buscamos el depósito principal (Asumimos ID 1, como en el Seeder)
                // Usamos la PK correcta: 'deposito_id'
                $depositoPrincipal = Deposito::first(); 

                if ($depositoPrincipal) {
                    Stock::create([
                        'productoID' => $producto->id,
                        'deposito_id' => $depositoPrincipal->deposito_id,
                        'cantidad_disponible' => 0, // El stock inicial es CERO (se ingresa con CU-23)
                        'stock_minimo' => 0,        // Se puede configurar después
                    ]);
                } else {
                    // Si no hay depósito, revertimos la transacción
                    throw new \Exception("No se encontró el depósito principal para crear el stock.");
                }
            }

            // 4. Registrar Auditoría (CU-25, Paso 9)
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'CREAR_PRODUCTO',
                'datos_nuevos' => json_encode($producto->toArray()),
                'datos_anteriores' => null, // Es una creación
                'usuarioID' => $userId,
                'motivo' => 'Registro inicial de producto',
                'detalles' => "Producto registrado: {$producto->nombre} ({$producto->codigo})"
            ]);

            Log::info("Producto registrado: ID {$producto->id} por Usuario ID {$userId}");

            return $producto;
        });
    }
}