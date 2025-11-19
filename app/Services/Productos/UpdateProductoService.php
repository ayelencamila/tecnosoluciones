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
    /**
     * Actualiza un producto y gestiona el historial de precios.
     * (CU-26)
     */
    public function handle(Producto $producto, array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($producto, $data, $userId) {
            
            // A. Capturamos datos previos para Auditoría
            $datosAnteriores = $producto->toArray();
            $preciosAnteriores = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosAnteriores['precios_vigentes'] = $preciosAnteriores;

            // 1. Actualizar datos maestros del Producto
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

            // 2. Actualizar Stock Mínimo (Configuración de Alerta)
            // CORRECCIÓN: Usamos $producto (no $cliente) y $data (no $validatedData)
            if (isset($data['stock_minimo'])) {
                // Actualizamos el mínimo en todos los depósitos donde exista el producto
                $producto->stocks()->update(['stock_minimo' => $data['stock_minimo']]);
            }

            // 3. Gestión de Historial de Precios
            $fechaActual = now()->toDateString();
            $fechaActualUTC = now()->utc()->toDateString();
            
            foreach ($data['precios'] as $precioInput) {
                
                $precioVigente = PrecioProducto::where('productoID', $producto->id)
                    ->where('tipoClienteID', $precioInput['tipoClienteID'])
                    ->whereNull('fechaHasta')
                    ->first();
                
                if ($precioVigente) {
                    // Comparar precios (formateando para evitar errores de flotantes)
                    $precioActual = number_format((float) $precioVigente->precio, 2, '.', '');
                    $precioNuevo = number_format((float) $precioInput['precio'], 2, '.', '');

                    if ($precioActual !== $precioNuevo) {
                        $fechaDesdeActual = Carbon::parse($precioVigente->fechaDesde)->utc()->toDateString();

                        if ($fechaDesdeActual == $fechaActualUTC) {
                            // Si el precio se creó hoy, corregimos el mismo registro
                            $precioVigente->precio = $precioNuevo;
                            $precioVigente->save();
                        } else {
                            // Si es viejo, cerramos historial y creamos nuevo
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
                    // Precio nuevo para un tipo de cliente que no tenía
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

            // 4. Auditoría con Motivo (CU-26)
            $datosNuevos = $producto->fresh()->toArray();
            $preciosNuevos = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosNuevos['precios_vigentes'] = $preciosNuevos;

            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'MODIFICAR_PRODUCTO',
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($datosNuevos),
                'motivo' => $data['motivo'], // El motivo viene del formulario
                'usuarioID' => $userId,
                'detalles' => "Producto modificado: {$producto->nombre}"
            ]);

            return $producto;
        });
    }
}