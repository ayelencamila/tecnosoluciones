<?php

namespace App\Services\Stock;

use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\TipoMovimientoStock; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegistrarMovimientoStockService
{
    public function handle(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Buscar el Stock y el Tipo de Movimiento
            $stock = Stock::findOrFail($data['stock_id']);
            $tipoMovimiento = TipoMovimientoStock::findOrFail($data['tipo_movimiento_id']);
            
            $cantidad = (int) $data['cantidad'];
            
            // 2. Calcular el impacto real (Signo * Cantidad)
            // Ej: Si signo es -1 (Salida) y cantidad 10 => -10
            $cambioReal = $cantidad * $tipoMovimiento->signo; 

            // Validar que no quede negativo si es una resta
            if ($cambioReal < 0 && abs($cambioReal) > $stock->cantidad_disponible) {
                 throw new Exception("Stock insuficiente para realizar este ajuste. Disponible: {$stock->cantidad_disponible}");
            }

            $stockAnterior = $stock->cantidad_disponible;
            
            // 3. Actualizar el Stock Maestro
            $stock->cantidad_disponible += $cambioReal;
            $stock->save();

            // 4. Registrar el Movimiento (Historial)
            MovimientoStock::create([
                'productoID' => $stock->productoID,
                'deposito_id' => $stock->deposito_id,
                'tipo_movimiento_id' => $tipoMovimiento->id, // Guardamos FK
                'cantidad' => $cantidad, // Guardamos siempre positivo en la cantidad nominal
                'signo' => $tipoMovimiento->signo, // Guardamos el signo histórico
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stock->cantidad_disponible,
                'motivo' => $data['motivo'],
                'user_id' => $userId,
                'fecha_movimiento' => now(),
                'referenciaTabla' => 'manual', // Indica que fue un ajuste manual
            ]);

            Log::info("Movimiento de stock registrado: {$tipoMovimiento->nombre} x{$cantidad} en Producto ID {$stock->productoID}");
        });
    }
}