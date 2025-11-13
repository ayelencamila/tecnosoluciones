<?php

namespace App\Services\Ventas;

use App\Events\VentaAnulada;
use App\Exceptions\Ventas\VentaYaAnuladaException;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnularVentaService
{
    public function handle(Venta $venta, string $motivo, int $userID): Venta
    {
        if ($venta->anulada) {
            throw new VentaYaAnuladaException($venta->numero_comprobante);
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
