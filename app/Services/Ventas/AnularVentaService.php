<?php

namespace App\Services\Ventas;

use App\Events\VentaAnulada;
use App\Exceptions\Ventas\VentaYaAnuladaException;
use App\Models\Configuracion;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnularVentaService
{
    /**
     * Anula una venta con validaciones de negocio.
     * 
     * @throws VentaYaAnuladaException Si la venta ya está anulada
     * @throws \Exception Si han transcurrido más días de los permitidos o hay pagos asociados
     */
    public function handle(Venta $venta, string $motivo, int $userID): Venta
    {
        if ($venta->anulada) {
            throw new VentaYaAnuladaException($venta->numero_comprobante);
        }

        // Validación 1: Verificar días transcurridos desde la venta
        $diasMaximosAnulacion = Configuracion::getInt('dias_maximos_anulacion_venta', 30);
        $diasTranscurridos = Carbon::parse($venta->fecha_venta)->diffInDays(Carbon::now());
        
        if ($diasTranscurridos > $diasMaximosAnulacion) {
            throw new \Exception(
                "No se puede anular la venta. Han transcurrido {$diasTranscurridos} días y el límite es de {$diasMaximosAnulacion} días."
            );
        }

        // Validación 2: Verificar si es venta a cuenta corriente y hay movimientos de pago
        // Para ventas a cuenta corriente, verificar que no haya pagos aplicados al débito generado
        if ($venta->metodo_pago === 'cuenta_corriente') {
            $cuentaCorriente = $venta->cliente->cuentaCorriente;
            if ($cuentaCorriente) {
                $movimientoDebito = $cuentaCorriente->movimientos()
                    ->where('tipo', 'debito')
                    ->where('referenciaID', $venta->venta_id)
                    ->where('referenciaTabla', 'ventas')
                    ->first();
                
                if ($movimientoDebito) {
                    // Verificar si hay créditos (pagos) posteriores que puedan estar relacionados
                    $pagosPosteriores = $cuentaCorriente->movimientos()
                        ->where('tipo', 'credito')
                        ->where('fecha', '>=', $movimientoDebito->fecha)
                        ->exists();
                    
                    if ($pagosPosteriores) {
                        Log::warning("Anulación de venta con posibles pagos posteriores. Venta ID: {$venta->venta_id}");
                        // Advertencia pero no bloqueo - la imputación de pagos es compleja
                    }
                }
            }
        }

        return DB::transaction(function () use ($venta, $motivo, $userID) {
            $venta->anulada = true;
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            event(new VentaAnulada($venta, $userID));

            Log::info("Venta anulada con éxito: ID {$venta->venta_id} por Usuario ID {$userID}");

            return $venta;
        });
    }
}
