<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarProductoService
{
    /**
     * Registra un nuevo producto y sus precios iniciales.
     * (CU-25)
     */
    public function handle(array $data, int $userId): Producto
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Crear Producto (Con stock inicial en la misma tabla)
            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'marca' => $data['marca'] ?? null,
                'unidadMedida' => $data['unidadMedida'],
                'categoriaProductoID' => $data['categoriaProductoID'],
                'estadoProductoID' => $data['estadoProductoID'],
                'stockActual' => $data['stockActual'] ?? 0,
                'stockMinimo' => $data['stockMinimo'] ?? 0,
            ]);

            // 2. Crear Precios Iniciales
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

            // 3. Registrar Auditoría
            // (Si tu modelo Producto ya tiene boot() con auditoría, esto podría ser redundante,
            // pero como el controlador original lo hacía manual, lo mantengo aquí por seguridad).
            Auditoria::create([
                'tabla_afectada' => 'productos', // Asegurate que coincida con tu columna en BD
                'registro_id' => $producto->id,
                'accion' => 'CREAR_PRODUCTO',
                'datos_nuevos' => json_encode($producto->toArray()), // Convertir a JSON
                'usuarioID' => $userId,
                'detalles' => "Producto registrado: {$producto->nombre} ({$producto->codigo})"
            ]);

            Log::info("Producto registrado: ID {$producto->id}");

            return $producto;
        });
    }
}