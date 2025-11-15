<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
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
            
            $datosAnteriores = $producto->toArray();

            // 1. Actualizar datos maestros del Producto
            $producto->update([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                // Nota: El stock generalmente se actualiza por movimientos, 
                // pero si el CU permite ajuste manual aquí, lo dejamos.
                'stockActual' => $data['stockActual'] ?? $producto->stockActual,
                'stockMinimo' => $data['stockMinimo'] ?? $producto->stockMinimo,
            ]);

            // 2. Gestión de Historial de Precios
            $fechaActual = now()->toDateString();
            
            foreach ($data['precios'] as $precioInput) {
                // Buscar el precio vigente para este tipo de cliente
                // (Asumiendo que tenés una relación o método auxiliar, sino lo hacemos manual)
                $precioVigente = PrecioProducto::where('productoID', $producto->id)
                    ->where('tipoClienteID', $precioInput['tipoClienteID'])
                    ->whereNull('fechaHasta') // Solo los vigentes
                    ->first();

                // Si existe y el precio cambió...
                if ($precioVigente && $precioVigente->precio != $precioInput['precio']) {
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

            // 3. Auditoría con Motivo (CU-26)
            Auditoria::create([
                'tabla_afectada' => 'productos',
                'registro_id' => $producto->id,
                'accion' => 'MODIFICAR_PRODUCTO',
                'datos_anteriores' => json_encode($datosAnteriores),
                'datos_nuevos' => json_encode($producto->fresh()->toArray()),
                'motivo' => $data['motivo'], // Requerido por el CU
                'usuarioID' => $userId,
                'detalles' => "Producto modificado: {$producto->nombre}"
            ]);

            return $producto;
        });
    }
}