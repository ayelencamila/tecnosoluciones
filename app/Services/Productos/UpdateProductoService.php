<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use Carbon\Carbon; // <-- CORRECCIÓN 1: Importado
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
            
            // Capturamos los datos ANTES de cualquier cambio
            $datosAnteriores = $producto->toArray();
            // También capturamos los precios vigentes anteriores para la auditoría
            $preciosAnteriores = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosAnteriores['precios_vigentes'] = $preciosAnteriores;


            // 1. Actualizar datos maestros del Producto (Catálogo)
            $producto->update([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'proveedor_habitual_id' => $data['proveedor_habitual_id'] ?? null, // <-- CORRECCIÓN 2: Añadido
                
            ]);

            // 2. Gestión de Historial de Precios (Tu lógica es buena, la mantenemos)
            $fechaActual = now()->toDateString();
            $fechaActualUTC = now()->utc()->toDateString();
            
            foreach ($data['precios'] as $precioInput) {
                
                $precioVigente = PrecioProducto::where('productoID', $producto->id)
                    ->where('tipoClienteID', $precioInput['tipoClienteID'])
                    ->whereNull('fechaHasta') // Solo los vigentes
                    ->first();
                
                if ($precioVigente) {
                    
                    $precioActual = number_format((float) $precioVigente->precio, 2, '.', '');
                    $precioNuevo = number_format((float) $precioInput['precio'], 2, '.', '');

                    // Si existe y el precio cambió...
                    if ($precioActual !== $precioNuevo) {
                        
                        $fechaDesdeActual = Carbon::parse($precioVigente->fechaDesde)->utc()->toDateString();

                        if ($fechaDesdeActual == ($fechaActualUTC)) {
                            // Si el precio se creó HOY, solo lo actualizamos
                            $precioVigente->precio = $precioNuevo;
                            $precioVigente->save();
                        
                        } else {
                            // Si es un precio antiguo, lo cerramos y creamos uno nuevo
                            // A. Cerrar el precio anterior
                            $precioVigente->update(['fechaHasta' => $fechaActual]);

                            // B. Crear el nuevo precio vigente
                            PrecioProducto::create([
                                'productoID' => $producto->id,
                                'tipoClienteID' => $precioInput['tipoClienteID'],
                                'precio' => $precioInput['precio'],
                                'fechaDesde' => $fechaActual,
                                'fechaHasta' => null,
                            ]);
                        }
                    }
                }
                // Si no existía precio para este tipo de cliente, lo creamos
                elseif (!$precioVigente) {
                    PrecioProducto::create([
                        'productoID' => $producto->id,
                        'tipoClienteID' => $precioInput['tipoClienteID'],
                        'precio' => $precioInput['precio'],
                        'fechaDesde' => $fechaActual,
                        'fechaHasta' => null,
                    ]);
                }
            }

            // 3. Auditoría con Motivo (CU-26, Paso 11)
            // Obtenemos los datos frescos del producto Y sus nuevos precios
            $datosNuevos = $producto->fresh()->toArray();
            $preciosNuevos = $producto->precios()->whereNull('fechaHasta')->get()->toArray();
            $datosNuevos['precios_vigentes'] = $preciosNuevos;

            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'MODIFICAR_PRODUCTO',
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($datosNuevos),
                'motivo' => $data['motivo'], // Requerido por el CU-26
                'usuarioID' => $userId,
                'detalles' => "Producto modificado: {$producto->nombre}"
            ]);

            return $producto;
        });
    }
}