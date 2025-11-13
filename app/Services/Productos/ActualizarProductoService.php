<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarProductoService
{
    /**
     * CU-26: Actualiza los datos maestros y gestiona la vigencia de precios (historial).
     *
     * @param Producto $producto El producto a actualizar.
     * @param array $validatedData Los datos validados del UpdateProductoRequest.
     * @param int $userID El ID del usuario que registra la acción (para auditoría).
     * @return Producto El modelo Producto actualizado.
     * @throws \Exception
     */
    public function handle(Producto $producto, array $validatedData, int $userID): Producto
    {
        DB::beginTransaction();
        try {
            $datosAnteriores = $producto->toArray();
            $fechaActual = now()->toDateString();
            $motivo = $validatedData['motivo'];

            // 1. ACTUALIZAR PRODUCTO (Datos Maestros y Stock Mínimo)
            $producto->update([
                'codigo' => $validatedData['codigo'],
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? null,
                'marca' => $validatedData['marca'] ?? null,
                'unidadMedida' => $validatedData['unidadMedida'],
                'categoriaProductoID' => $validatedData['categoriaProductoID'],
                'estadoProductoID' => $validatedData['estadoProductoID'],
                // El stockActual/stockMinimo en el producto es un placeholder. 
                // Actualizamos el Stock Mínimo en el registro Stock.
                'stockMinimo' => $validatedData['stockMinimo'] ?? 0, 
            ]);

            // 2. ACTUALIZAR STOCK MÍNIMO en el registro Stock (Asumiendo Depósito 1)
            $stockRegistro = $producto->stocks()->where('deposito_id', 1)->first();
            if ($stockRegistro) {
                $stockRegistro->update([
                    'stock_minimo' => $validatedData['stockMinimo'] ?? 0,
                ]);
            }

            // 3. ACTUALIZAR PRECIOS (Historial de Precios)
            foreach ($validatedData['precios'] as $precioData) {
                // Asumo que Producto.php tiene el método precioVigente($tipoClienteID)
                $precioVigente = $producto->precioVigente($precioData['tipoClienteID']); 

                // Si el precio actual es diferente al precio enviado, cerramos el anterior y creamos uno nuevo.
                if ($precioVigente && (float)$precioVigente->precio != (float)$precioData['precio']) {
                    
                    // Cerrar vigencia del precio anterior
                    $precioVigente->update(['fechaHasta' => $fechaActual]);

                    // Crear nuevo precio (histórico)
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                    ]);
                } elseif (!$precioVigente) {
                    // Si no existía, lo creamos
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                    ]);
                }
            }

            // 4. REGISTRAR EN AUDITORÍA
            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_PRODUCTO,
                'productos',
                $producto->id,
                $datosAnteriores,
                $producto->fresh()->toArray(),
                $motivo,
                'Producto modificado: '.$producto->nombre.'. Motivo: '.$motivo,
                $userID
            );

            DB::commit();
            return $producto->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en ActualizarProductoService: '.$e->getMessage());
            throw new \Exception('Error de servicio al modificar el producto: '.$e->getMessage());
        }
    }
}