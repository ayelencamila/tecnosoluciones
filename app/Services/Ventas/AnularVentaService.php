<?php

namespace App\Services\Ventas;

use App\Events\VentaAnulada;
use App\Exceptions\Ventas\VentaYaAnuladaException;
use App\Models\Venta;
use App\Models\Stock; // Necesario para la reversión
use App\Models\MovimientoStock; // Necesario para la trazabilidad
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnularVentaService
{
    /**
     * Procesa la anulación de una venta (CU-06, Pasos 6-10).
     */
    public function handle(Venta $venta, string $motivo, int $userID): Venta
    {
        // Validación Pre-Condición (CU-06, Paso 6)
        if ($venta->anulada) {
            throw new VentaYaAnuladaException($venta->numero_comprobante);
        }

        return DB::transaction(function () use ($venta, $motivo, $userID) {
            // 1. Marcar la Venta como Anulada (Paso 8, parte 1)
            $venta->anulada = true;
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            // 2. Revertir Stock y registrar Movimiento (Paso 8, parte 2 / RF5)
            $this->revertirStock($venta);

            // 3. Disparar Evento para reversión de CC y Auditoría (Paso 9)
            // Esto cumple con el flujo: Se emite un registro interno de crédito o se ajusta la cuenta corriente.
            event(new VentaAnulada($venta, $userID));

            Log::info("Venta anulada con éxito: ID {$venta->venta_id} por Usuario ID {$userID}");

            // 4. Confirmación (Paso 10, manejado en el Controller con la redirección)
            return $venta;
        });
    }

    /**
     * Reincorpora el stock de los productos vendidos y registra el MovimientoStock.
     * Esta lógica DEBE estar en el Service (Coordinador) para garantizar la atomicidad.
     */
    private function revertirStock(Venta $venta): void
    {
        // Cargamos los detalles de la venta si no están ya cargados
        $venta->load('detalles.producto'); 
        
        foreach ($venta->detalles as $detalle) {
            $producto = $detalle->producto;
            $cantidadRevertida = (int) $detalle->cantidad;

            // Solo reincorporar si es un producto físico/no servicio
            if ($producto && $producto->unidadMedida !== 'Servicio') {
                
                // Asumimos Depósito Único: buscamos el registro de stock para ese producto
                $stockRegistro = Stock::where('productoID', $producto->id)->first();

                if (!$stockRegistro) {
                    // Excepción 6a: Inconsistencia de inventario. No encontramos el registro para reincorporar.
                    Log::error("Error 6a: Stock no encontrado para Producto ID {$producto->id} durante anulación de Venta ID {$venta->venta_id}.");
                    // No abortamos la anulación de la VENTA, pero registramos la inconsistencia para revisión manual.
                    // Esto cumple parcialmente con Excepción 6a: "Anulación de venta procesada, pero se detectó una inconsistencia..."
                    continue; 
                }

                $stockAnterior = $stockRegistro->cantidad_disponible;

                // 1. Reincorporar Stock (Delegar a Information Expert Stock)
                $stockRegistro->incrementar($cantidadRevertida); 
                
                // 2. Registrar Movimiento de Stock (AUDITORÍA DE MOVIMIENTO - SALIDA)
                MovimientoStock::create([
                    'productoID' => $producto->id,
                    'tipoMovimiento' => 'ENTRADA', 
                    'cantidad' => $cantidadRevertida,
                    'stockAnterior' => $stockAnterior,
                    'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible,
                    'motivo' => 'Anulación Venta N° ' . $venta->numero_comprobante . ' - ' . $venta->motivo_anulacion,
                    'referenciaID' => $venta->venta_id,
                    'referenciaTabla' => 'ventas',
                    'user_id' => auth()->id(), 
                    'fecha_movimiento' => now(), 
                ]);
            }
        }
    }
}