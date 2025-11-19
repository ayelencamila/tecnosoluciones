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
    public function handle(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            // 1. Obtener el registro de Stock (Experto en Información)
            // Usamos lockForUpdate para evitar condiciones de carrera si dos usuarios ajustan al mismo tiempo.
            $stock = Stock::where('stock_id', $data['stock_id'])->lockForUpdate()->firstOrFail();
            
            $cantidad = (int) $data['cantidad'];
            $stockAnterior = $stock->cantidad_disponible;
            
            // 2. Validar y Aplicar Lógica según Tipo
            if ($data['tipoMovimiento'] === 'SALIDA') {
                if (!$stock->tieneDisponibilidad($cantidad)) {
                    throw new Exception("Stock insuficiente. Disponible: {$stockAnterior}, Solicitado: {$cantidad}");
                }
                $stock->descontar($cantidad); // Método del modelo Stock
            } else {
                // ENTRADA o AJUSTE (Positivo)
                // Nota: Si quisieras ajustes negativos con "AJUSTE", requeriría lógica extra. 
                // Por ahora asumimos que AJUSTE suma (como carga inicial) o usas SALIDA para restar.
                $stock->incrementar($cantidad);
            }

            // 3. Registrar el Movimiento (Historial/Trazabilidad)
            MovimientoStock::create([
                'stock_id' => $stock->stock_id,
                'productoID' => $stock->productoID, // Redundancia útil para búsquedas rápidas
                'tipoMovimiento' => $data['tipoMovimiento'],
                'cantidad' => $cantidad,
                'stockAnterior' => $stockAnterior,
                'stockNuevo' => $stock->cantidad_disponible,
                'motivo' => $data['motivo'],
                'user_id' => $userId, // Auditoría de quién lo hizo
                // referenciaID y referenciaTabla quedan null porque es manual
            ]);
        });
    }
}