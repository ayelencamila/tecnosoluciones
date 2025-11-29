<?php

namespace App\Services\Stock;

use App\Models\Stock;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;
use Exception;

class RegistrarMovimientoStockService
{
    /**
     * Ejecuta la lógica del CU-30: Registrar Movimiento Manual.
     */
    public function handle(array $data, int $userId)
{
    return DB::transaction(function () use ($data, $userId) {
        $stock = Stock::findOrFail($data['stock_id']);
        $tipoMovimiento = TipoMovimientoStock::findOrFail($data['tipo_movimiento_id']);
        
        $cantidad = (int) $data['cantidad'];
        $cambioReal = $cantidad * $tipoMovimiento->signo; // Magia: 10 * -1 = -10

        $stockAnterior = $stock->cantidad_disponible;
        
        // Actualizamos el stock
        $stock->cantidad_disponible += $cambioReal;
        $stock->save();

        // Registramos movimiento
        MovimientoStock::create([
            'productoID' => $stock->productoID,
            'deposito_id' => $stock->deposito_id,
            'tipo_movimiento_id' => $tipoMovimiento->id, // FK
            'cantidad' => $cantidad, // Siempre positiva
            'signo' => $tipoMovimiento->signo, // Guardamos el signo histórico
            'stockAnterior' => $stockAnterior,
            'stockNuevo' => $stock->cantidad_disponible,
            'motivo' => $data['motivo'],
            'user_id' => $userId,
            'fecha_movimiento' => now(),
        ]);
    });
}
}