<?php

namespace App\Services\Reparaciones;

use App\Events\ReparacionRegistrada; // Asegúrate de crear este evento después
use App\Exceptions\Ventas\SinStockException; // Reutilizamos tu excepción
use App\Models\Reparacion;
use App\Models\DetalleReparacion;
use App\Models\ImagenReparacion;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\EstadoReparacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;

class RegistrarReparacionService
{
    public function handle(array $datosValidados, int $usuarioID): Reparacion
    {
        // 1. VALIDACIÓN PREVIA DE STOCK (Igual que en tu VentaService)
        // Si se agregan repuestos desde el inicio (ej: cambio de módulo obvio)
        if (!empty($datosValidados['items'])) {
            $this->validarStockPrevio($datosValidados['items']);
        }

        return DB::transaction(function () use ($datosValidados, $usuarioID) {
            
            // 2. PREPARAR DATOS MAESTROS
            // Buscamos el estado inicial 'Recibido'
            $estadoInicial = EstadoReparacion::where('nombreEstado', 'Recibido')->firstOrFail();
            
            // Generamos un código único (Ej: REP-20251020-1234)
            $codigoReparacion = 'REP-' . Carbon::now()->format('Ymd') . '-' . time();

            // 3. CREAR LA REPARACIÓN (Cabecera)
            $reparacion = Reparacion::create([
                'clienteID' => $datosValidados['clienteID'],
                'tecnico_id' => null, // Se asigna luego o si viene en el request
                'estado_reparacion_id' => $estadoInicial->estadoReparacionID,
                'codigo_reparacion' => $codigoReparacion,
                'equipo_marca' => $datosValidados['equipo_marca'],
                'equipo_modelo' => $datosValidados['equipo_modelo'],
                'numero_serie_imei' => $datosValidados['numero_serie_imei'] ?? null,
                'clave_bloqueo' => $datosValidados['clave_bloqueo'] ?? null,
                'accesorios_dejados' => $datosValidados['accesorios_dejados'] ?? null,
                'falla_declarada' => $datosValidados['falla_declarada'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
                'fecha_ingreso' => Carbon::now(),
                'fecha_promesa' => $datosValidados['fecha_promesa'] ?? null,
                'costo_mano_obra' => 0, // Se define al diagnosticar/cerrar
                'total_final' => 0,
            ]);

            // 4. GUARDAR IMÁGENES (NUEVO REQUERIMIENTO)
            if (isset($datosValidados['imagenes'])) {
                $this->procesarImagenes($reparacion, $datosValidados['imagenes']);
            }

            // 5. PROCESAR ITEMS (Repuestos o Servicios iniciales)
            if (!empty($datosValidados['items'])) {
                $this->procesarItems($reparacion, $datosValidados['items']);
            }

            // 6. EVENTO Y LOG
            // Asegúrate de crear: php artisan make:event ReparacionRegistrada
            // event(new ReparacionRegistrada($reparacion, $usuarioID)); 

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
                // Guardar en storage/app/public/reparaciones/{anio}/{id_reparacion}
                $ruta = $imagen->storePublicly(
                    "reparaciones/" . date('Y') . "/{$reparacion->reparacionID}", 
                    'public'
                );

                ImagenReparacion::create([
                    'reparacion_id' => $reparacion->reparacionID,
                    'ruta_archivo' => $ruta,
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'etapa' => 'ingreso', // Las fotos al registrar son del ingreso
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
            
            // Usamos el precio de lista base (o puedes implementar lógica de precios compleja aquí)
            // Para reparaciones, solemos usar el precio minorista base.
            // Supondré que tienes un método para obtener el precio base, o usamos el primer precio activo.
            $precioUnitario = $producto->precios()->latest('fechaDesde')->first()?->precio ?? 0; 
            
            $cantidad = $itemData['cantidad'];
            $subtotal = $precioUnitario * $cantidad;

            // A) Crear Detalle
            DetalleReparacion::create([
                'reparacion_id' => $reparacion->reparacionID,
                'producto_id'   => $producto->id,
                'cantidad'      => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal'      => $subtotal
            ]);

            // B) Descontar Stock (Estrategia Híbrida)
            // Usamos tu lógica: Si NO es servicio, descuenta stock.
            if ($producto->unidadMedida !== 'Servicio') {
                $this->descontarStock($producto, $cantidad, $reparacion);
            }
        }
    }

    /**
     * Lógica de descuento de stock extraída (Reutilizando lógica de VentaService)
     */
    private function descontarStock(Producto $producto, int $cantidad, Reparacion $reparacion): void
    {
        // Buscar registro de stock (asumiendo depósito único por ahora o lógica global)
        $stockRegistro = Stock::where('productoID', $producto->id)->first();

        if (!$stockRegistro) {
             throw new SinStockException($producto->nombre, $cantidad, 0, "No hay registro de stock.");
        }

        $stockAnterior = $stockRegistro->cantidad_disponible;
        
        // Decrementar
        $stockRegistro->decrement('cantidad_disponible', $cantidad);

        // Registrar Movimiento
        MovimientoStock::create([
            'productoID' => $producto->id,
            'tipoMovimiento' => 'SALIDA', // O 'CONSUMO_REPARACION' si prefieres distinguir
            'cantidad' => $cantidad,
            'stockAnterior' => $stockAnterior,
            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
            'motivo' => 'Uso en Reparación: ' . $reparacion->codigo_reparacion,
            'referenciaID' => $reparacion->reparacionID,
            'referenciaTabla' => 'reparaciones',
        ]);
    }

    /**
     * Validación previa idéntica a tu VentaService
     */
    private function validarStockPrevio(array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            
            if ($producto->unidadMedida !== 'Servicio') {
                 if (! $producto->tieneStock($item['cantidad'])) {
                    // Usamos tu accessor stock_total del Modelo Producto
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }
}