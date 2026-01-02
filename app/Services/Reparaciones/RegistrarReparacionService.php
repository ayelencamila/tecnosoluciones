<?php

namespace App\Services\Reparaciones;

use App\Events\ReparacionRegistrada;
use App\Exceptions\Ventas\SinStockException;
use App\Models\Reparacion;
use App\Models\DetalleReparacion;
use App\Models\ImagenReparacion;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\EstadoReparacion;
use App\Models\TipoMovimientoStock; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;

class RegistrarReparacionService
{
    public function handle(array $datosValidados, int $usuarioID): Reparacion
    {
        // 1. VALIDACIÓN PREVIA (Optimista - Fail Fast)
        if (!empty($datosValidados['items'])) {
            $this->validarStockPrevio($datosValidados['items']);
        }

        return DB::transaction(function () use ($datosValidados, $usuarioID) {
            
            // 2. PREPARAR DATOS MAESTROS
            $estadoInicial = EstadoReparacion::where('nombreEstado', 'Recibido')->firstOrFail();
            $codigoReparacion = 'REP-' . Carbon::now()->format('Ymd') . '-' . time();

            // 3. CREAR LA REPARACIÓN (Cabecera)
            $reparacion = Reparacion::create([
                'clienteID' => $datosValidados['clienteID'],
                'tecnico_id' => null, 
                'estado_reparacion_id' => $estadoInicial->estadoReparacionID,
                'codigo_reparacion' => $codigoReparacion,
                'marca_id' => $datosValidados['marca_id'],
                'modelo_id' => $datosValidados['modelo_id'],
                'numero_serie_imei' => $datosValidados['numero_serie_imei'] ?? null,
                'clave_bloqueo' => $datosValidados['clave_bloqueo'] ?? null,
                'accesorios_dejados' => $datosValidados['accesorios_dejados'] ?? null,
                'falla_declarada' => $datosValidados['falla_declarada'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
                'fecha_ingreso' => Carbon::now(),
                'fecha_promesa' => $datosValidados['fecha_promesa'] ?? null,
                'costo_mano_obra' => 0, 
                'total_final' => 0,
            ]);

            // 4. GUARDAR IMÁGENES
            if (isset($datosValidados['imagenes'])) {
                $this->procesarImagenes($reparacion, $datosValidados['imagenes']);
            }

            // 5. PROCESAR ITEMS (Repuestos iniciales) CON BLOQUEO
            if (!empty($datosValidados['items'])) {
                $this->procesarItems($reparacion, $datosValidados['items'], $usuarioID);
            }

            Log::info("Reparación registrada con éxito: ID {$reparacion->reparacionID} - Código: {$codigoReparacion}");

            return $reparacion;
        });
    }

    private function procesarImagenes(Reparacion $reparacion, array $imagenes): void
    {
        foreach ($imagenes as $imagen) {
            if ($imagen instanceof UploadedFile) {
                $ruta = $imagen->storePublicly(
                    "reparaciones/" . date('Y') . "/{$reparacion->reparacionID}", 
                    'public'
                );

                ImagenReparacion::create([
                    'reparacion_id' => $reparacion->reparacionID,
                    'ruta_archivo' => $ruta,
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'etapa' => 'ingreso',
                ]);
            }
        }
    }

    private function procesarItems(Reparacion $reparacion, array $items, int $usuarioID): void
    {
        foreach ($items as $itemData) {
            $producto = Producto::findOrFail($itemData['producto_id']);
            
            // Precio histórico (snapshot)
            $precioUnitario = $producto->precios()->latest('fechaDesde')->first()?->precio ?? 0; 
            
            $cantidad = $itemData['cantidad'];
            $subtotal = $precioUnitario * $cantidad;

            // Registrar detalle
            DetalleReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'producto_id'   => $producto->id,
                'cantidad'      => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal'      => $subtotal
            ]);

            // Descuento de Stock Seguro
            if ($producto->unidadMedida !== 'Servicio') {
                $this->descontarStock($producto, $cantidad, $reparacion, $usuarioID);
            }
        }
    }

    private function descontarStock(Producto $producto, int $cantidad, Reparacion $reparacion, int $usuarioID): void
    {
        // 1. Obtener Tipo de Movimiento Dinámicamente (SIN HARDCODEO)
        // Buscamos 'Salida (Venta)' que es el estándar definido en el Seeder para salidas.
        $tipoMovimiento = TipoMovimientoStock::where('nombre', 'Salida (Venta)')->first();

        if (!$tipoMovimiento) {
            // Protección contra datos maestros faltantes
            throw new \Exception("Error de Configuración Crítico: No se encontró el tipo de movimiento 'Salida (Venta)' en la base de datos.");
        }

        // 2. CORRECCIÓN ACID: Bloqueo pesimista
        $stockRegistro = Stock::where('productoID', $producto->id)
                              ->lockForUpdate() // PARA EVITAR CONDICIÓN DE CARRERA
                              ->first();

        if (!$stockRegistro) {
             throw new SinStockException($producto->nombre, $cantidad, 0, "No hay registro de stock para este producto.");
        }

        // 3. Validación estricta dentro del bloqueo
        if ($stockRegistro->cantidad_disponible < $cantidad) {
            throw new SinStockException($producto->nombre, $cantidad, $stockRegistro->cantidad_disponible);
        }

        $stockAnterior = $stockRegistro->cantidad_disponible;
        
        $stockRegistro->decrement('cantidad_disponible', $cantidad);

        // 4. Crear Movimiento usando datos dinámicos
        MovimientoStock::create([
            'productoID' => $producto->id,
            'deposito_id' => $stockRegistro->deposito_id,
            'tipoMovimiento' => 'SALIDA', 
            'tipo_movimiento_id' => $tipoMovimiento->id, 
            'cantidad' => $cantidad,
            'signo' => $tipoMovimiento->signo, 
            'stockAnterior' => $stockAnterior,
            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
            'motivo' => 'Uso en Reparación: ' . $reparacion->codigo_reparacion,
            'referenciaID' => $reparacion->reparacionID,
            'referenciaTabla' => 'reparaciones',
            'user_id' => $usuarioID,
            'fecha_movimiento' => now()
        ]);
    }

    private function validarStockPrevio(array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            
            if ($producto->unidadMedida !== 'Servicio') {
                 if (! $producto->tieneStock($item['cantidad'])) {
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }
}