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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;

class RegistrarReparacionService
{
    public function handle(array $datosValidados, int $usuarioID): Reparacion
    {
        // 1. VALIDACIÓN PREVIA DE STOCK
        if (!empty($datosValidados['items'])) {
            $this->validarStockPrevio($datosValidados['items']);
        }

        return DB::transaction(function () use ($datosValidados, $usuarioID) {
            
            // 2. PREPARAR DATOS MAESTROS
            // Buscamos el estado inicial 'Recibido'
            $estadoInicial = EstadoReparacion::where('nombreEstado', 'Recibido')->firstOrFail();
            
            // Generamos un código único
            $codigoReparacion = 'REP-' . Carbon::now()->format('Ymd') . '-' . time();

            // 3. CREAR LA REPARACIÓN (Cabecera)
            $reparacion = Reparacion::create([
                'clienteID' => $datosValidados['clienteID'],
                'tecnico_id' => null, 
                'estado_reparacion_id' => $estadoInicial->estadoReparacionID,
                'codigo_reparacion' => $codigoReparacion,
                
                // --- CAMBIO CLAVE (Misión 3 - Configurabilidad) ---
                // Guardamos los IDs seleccionados en lugar de texto libre
                'marca_id' => $datosValidados['marca_id'],
                'modelo_id' => $datosValidados['modelo_id'],
                // --------------------------------------------------

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

            // 5. PROCESAR ITEMS (Repuestos iniciales)
            if (!empty($datosValidados['items'])) {
                $this->procesarItems($reparacion, $datosValidados['items']);
            }

            Log::info("Reparación registrada con éxito: ID {$reparacion->reparacionID} - Código: {$codigoReparacion}");

            return $reparacion;
        });
    }

    /**
     * Maneja la subida de archivos y el registro en BD
     */
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

    /**
     * Procesa repuestos (físicos) y servicios (lógicos)
     */
    private function procesarItems(Reparacion $reparacion, array $items): void
    {
        foreach ($items as $itemData) {
            $producto = Producto::findOrFail($itemData['producto_id']);
            
            $precioUnitario = $producto->precios()->latest('fechaDesde')->first()?->precio ?? 0; 
            
            $cantidad = $itemData['cantidad'];
            $subtotal = $precioUnitario * $cantidad;

            DetalleReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'producto_id'   => $producto->id,
                'cantidad'      => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal'      => $subtotal
            ]);

            if ($producto->unidadMedida !== 'Servicio') {
                $this->descontarStock($producto, $cantidad, $reparacion);
            }
        }
    }

    /**
     * Lógica de descuento de stock
     */
    private function descontarStock(Producto $producto, int $cantidad, Reparacion $reparacion): void
    {
        $stockRegistro = Stock::where('productoID', $producto->id)->first();

        if (!$stockRegistro) {
             throw new SinStockException($producto->nombre, $cantidad, 0, "No hay registro de stock.");
        }

        $stockAnterior = $stockRegistro->cantidad_disponible;
        
        $stockRegistro->decrement('cantidad_disponible', $cantidad);

        MovimientoStock::create([
            'productoID' => $producto->id,
            'tipoMovimiento' => 'SALIDA', 
            'cantidad' => $cantidad,
            'stockAnterior' => $stockAnterior,
            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
            'motivo' => 'Uso en Reparación: ' . $reparacion->codigo_reparacion,
            'referenciaID' => $reparacion->reparacionID,
            'referenciaTabla' => 'reparaciones',
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