<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductoService 
{
    // CORRECCIÓN: Agregué los nombres de las variables ($producto, $validatedData, $userID) que faltaban
    public function handle(Producto $producto, array $validatedData, int $userID): Producto
    {
        DB::beginTransaction();
        try {
            $datosAnteriores = $producto->toArray();
            $fechaActual = now()->toDateString();
            $motivo = $validatedData['motivo'];

            // 1. ACTUALIZAR PRODUCTO
            $producto->update([
                'codigo' => $validatedData['codigo'],
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? null,
                'marca_id' => $validatedData['marca_id'] ?? null,
                'unidad_medida_id' => $validatedData['unidad_medida_id'],
                'categoriaProductoID' => $validatedData['categoriaProductoID'],
                'estadoProductoID' => $validatedData['estadoProductoID'],
                'proveedor_habitual_id' => $validatedData['proveedor_habitual_id'] ?? null,
            ]);

            // 2. ACTUALIZAR STOCK MÍNIMO
            if (isset($validatedData['stockMinimo'])) {
                $stockRegistro = $producto->stocks()->first(); 
                if ($stockRegistro) {
                    $stockRegistro->update([
                        'stock_minimo' => $validatedData['stockMinimo'],
                    ]);
                }
            }

            // 3. ACTUALIZAR PRECIOS
            foreach ($validatedData['precios'] as $precioData) {
                 $precioVigente = PrecioProducto::where('productoID', $producto->id)
                    ->where('tipoClienteID', $precioData['tipoClienteID'])
                    ->whereNull('fechaHasta')
                    ->first();

                 if ($precioVigente && (float)$precioVigente->precio != (float)$precioData['precio']) {
                    $precioVigente->update(['fechaHasta' => $fechaActual]);
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'usuarioID' => $userID,
                    ]);
                 } elseif (!$precioVigente) {
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioData['tipoClienteID'],
                        'precio' => $precioData['precio'],
                        'fechaDesde' => $fechaActual,
                        'usuarioID' => $userID,
                    ]);
                 }
            }

            // 4. AUDITORÍA
            $datosNuevos = $producto->fresh()->toArray();
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'MODIFICAR_PRODUCTO',
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($datosNuevos),
                'motivo' => $motivo,
                'detalles' => 'Producto modificado: '.$producto->nombre,
                'usuarioID' => $userID
            ]);

            DB::commit();
            return $producto->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en UpdateProductoService: '.$e->getMessage());
            throw new \Exception('Error de servicio al modificar el producto: '.$e->getMessage());
        }
    }
}
