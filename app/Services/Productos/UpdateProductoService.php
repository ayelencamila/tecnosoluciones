<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateProductoService
{
    public function handle(Producto $producto, array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($producto, $data, $userId) {
            
            $datosAnteriores = $producto->toArray();
            $preciosAnteriores = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosAnteriores['precios_vigentes'] = $preciosAnteriores;

            // 1. Actualizar datos maestros
            $producto->update([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'proveedor_habitual_id' => $data['proveedor_habitual_id'] ?? null,
            ]);

            // [ELIMINADO] Ya no actualizamos stock_minimo aquí. Eso corresponde al módulo de Stock.

            // 2. Gestión de Historial de Precios
            $fechaActual = now()->toDateString();
            $fechaActualUTC = now()->utc()->toDateString();
            
            foreach ($data['precios'] as $precioInput) {
                $precioVigente = PrecioProducto::where('productoID', $producto->id)
                    ->where('tipoClienteID', $precioInput['tipoClienteID'])
                    ->whereNull('fechaHasta')
                    ->first();
                
                if ($precioVigente) {
                    $precioActual = number_format((float) $precioVigente->precio, 2, '.', '');
                    $precioNuevo = number_format((float) $precioInput['precio'], 2, '.', '');

                    if ($precioActual !== $precioNuevo) {
                        $fechaDesdeActual = Carbon::parse($precioVigente->fechaDesde)->utc()->toDateString();

                        if ($fechaDesdeActual == $fechaActualUTC) {
                            $precioVigente->precio = $precioNuevo;
                            $precioVigente->save();
                        } else {
                            $precioVigente->update(['fechaHasta' => $fechaActual]);
                            PrecioProducto::create([
                                'productoID' => $producto->id,
                                'tipoClienteID' => $precioInput['tipoClienteID'],
                                'precio' => $precioInput['precio'],
                                'fechaDesde' => $fechaActual,
                                'fechaHasta' => null,
                                'usuarioID' => $userId,
                            ]);
                        }
                    }
                } elseif (!$precioVigente) {
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioInput['tipoClienteID'],
                        'precio' => $precioInput['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                        'usuarioID' => $userId,
                    ]);
                }
            }

            // 3. Auditoría
            $datosNuevos = $producto->fresh()->toArray();
            $preciosNuevos = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosNuevos['precios_vigentes'] = $preciosNuevos;

            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'MODIFICAR_PRODUCTO',
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($datosNuevos),
                'motivo' => $data['motivo'],
                'usuarioID' => $userId,
                'detalles' => "Producto modificado: {$producto->nombre}"
            ]);

            return $producto;
        });
    }
}