<?php

namespace App\Services\Ventas;

use App\Events\VentaAnulada;
use App\Exceptions\Ventas\VentaYaAnuladaException;
use App\Models\Venta;
use App\Models\Stock; 
use App\Models\MovimientoStock; 
use App\Models\EstadoVenta; // <--- Importante: Usamos el modelo de estados
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnularVentaService
{
    /**
     * Procesa la anulación de una venta (CU-06).
     */
    public function handle(Venta $venta, string $motivo, int $userID): Venta
    {
        // Validación Pre-Condición: Verificamos si el estado YA es 'Anulada' (ID 3)
        if ($venta->estado_venta_id === EstadoVenta::ANULADA) {
            throw new VentaYaAnuladaException($venta->numero_comprobante);
        }

        return DB::transaction(function () use ($venta, $motivo, $userID) {
            // 1. Cambiar Estado a ANULADA (En vez de booleano)
            $venta->estado_venta_id = EstadoVenta::ANULADA;
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            // 2. Revertir Stock
            $this->revertirStock($venta, $userID);

            // 3. CU-32: Registrar comprobante de Nota de Crédito Interna
            $tipoComprobante = \DB::table('tipos_comprobante')->where('codigo', 'NOTA_CREDITO_INTERNA')->value('tipo_id');
            $estadoEmitido = \DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');

            if ($tipoComprobante && $estadoEmitido) {
                \App\Models\Comprobante::create([
                    'tipo_entidad' => $venta->getMorphClass(),
                    'entidad_id' => $venta->venta_id,
                    'usuario_id' => $userID,
                    'tipo_comprobante_id' => $tipoComprobante,
                    'numero_correlativo' => 'NC-' . $venta->numero_comprobante,
                    'fecha_emision' => now(),
                    'estado_comprobante_id' => $estadoEmitido,
                ]);
            }

            // 4. Disparar Evento
            event(new VentaAnulada($venta, $userID));

            Log::info("Venta anulada con éxito: ID {$venta->venta_id} por Usuario ID {$userID}");

            return $venta;
        });
    }

    /**
     * Reincorpora el stock de los productos vendidos.
     */
    private function revertirStock(Venta $venta, int $userID): void
    {
        $venta->load('detalles.producto'); 
        
        foreach ($venta->detalles as $detalle) {
            $producto = $detalle->producto;
            $cantidadRevertida = (int) $detalle->cantidad;

            if ($producto && $producto->unidadMedida !== 'Servicio') {
                
                // Buscamos stock por productoID (asumiendo que Stock no cambió de nombre)
                // Si DetalleVenta usa 'producto_id', asegúrate de mapearlo bien.
                $stockRegistro = Stock::where('productoID', $producto->id)->first();

                if ($stockRegistro) {
                    $stockAnterior = $stockRegistro->cantidad_disponible;

                    // 1. Incrementar (Devolver)
                    $stockRegistro->increment('cantidad_disponible', $cantidadRevertida); 
                    
                    // 2. Registrar Movimiento (ENTRADA por Anulación)
                    MovimientoStock::create([
                        'productoID' => $producto->id,
                        'tipoMovimiento' => 'ENTRADA', 
                        'cantidad' => $cantidadRevertida,
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                        'motivo' => 'Anulación Venta ' . $venta->numero_comprobante,
                        'referenciaID' => $venta->venta_id,
                        'referenciaTabla' => 'ventas',
                        'user_id' => $userID, 
                        // 'fecha_movimiento' => now(), // Si tu migración lo pide, descomenta
                    ]);
                } else {
                    Log::error("Stock no encontrado para Producto ID {$producto->id} al anular Venta {$venta->venta_id}");
                }
            }
        }
    }
}