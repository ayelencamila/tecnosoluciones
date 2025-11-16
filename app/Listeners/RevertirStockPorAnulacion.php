<?php

namespace App\Listeners;

use App\Events\VentaAnulada;
use App\Models\MovimientoStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevertirStockPorAnulacion implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VentaAnulada $event): void
    {
        $venta = $event->venta;
        $venta->loadMissing('detalles.producto');

        try {
            DB::transaction(function () use ($venta) {
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    $cantidad = $detalle->cantidad;

                    $stockAnterior = $producto->stockActual;
                    $stockNuevo = $stockAnterior + $cantidad;

                    $producto->stockActual = $stockNuevo;
                    $producto->save();

                    MovimientoStock::create([
                        'productoID' => $producto->id,
                        'tipoMovimiento' => 'Devolución por Anulación',
                        'cantidad' => $cantidad,
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo' => $stockNuevo,
                        'motivo' => 'Anulación Venta '.$venta->numero_comprobante,
                        'referenciaID' => $detalle->detalle_venta_id, // PK correcta
                        'referenciaTabla' => 'detalle_ventas',
                    ]);
                }
            });

            Log::info("Stock REVERTIDO (con auditoría) para Venta ID: {$venta->venta_id}");

        } catch (\Exception $e) {
            Log::error("Error al REVERTIR stock para Venta ID {$venta->venta_id}: ".$e->getMessage());
        }
    }
}
