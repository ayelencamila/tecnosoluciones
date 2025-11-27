<?php

namespace App\Services\Pagos;

use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegistrarPagoService
{
    /**
     * Registra un pago, actualiza la CC e imputa automáticamente a las ventas más antiguas (FIFO).
     * (CU-10)
     */
    public function handle(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Obtener Cliente y Validar
            $cliente = Cliente::with('cuentaCorriente')->findOrFail($data['clienteID']);

            // 2. Crear el Registro del Pago (El Recibo)
            $pago = Pago::create([
                'clienteID'   => $cliente->clienteID,
                'user_id'     => $userId,
                'monto'       => $data['monto'],
                
                // Guardamos el ID, no el string
                'medioPagoID' => $data['medioPagoID'], 
                
                'fecha_pago'  => now(),
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // 3. Actualizar el Saldo Global de la Cuenta Corriente (Libro Mayor)
            if ($cliente->cuentaCorriente) {
                $descripcion = "Pago Recibido - Recibo: {$pago->numero_recibo}";
                
                $cliente->cuentaCorriente->registrarCredito(
                    $pago->monto,
                    $descripcion,
                    $pago->pago_id,
                    'pagos',
                    $userId
                );

                // Lógica extra: Si paga todo, desbloquear automáticamente si estaba bloqueado
                if ($cliente->cuentaCorriente->saldo <= 0 && $cliente->cuentaCorriente->estaBloqueada()) {
                    $cliente->cuentaCorriente->desbloquear("Desbloqueo automático por pago total", $userId);
                }
            }

            // 4. Imputación Automática FIFO (First In, First Out)
            // "El dinero mata la deuda más vieja primero"
            $this->imputarPagoAutomaticamente($pago, $cliente);

            Log::info("Pago registrado e imputado: ID {$pago->pago_id} - Cliente {$cliente->clienteID}");

            return $pago;
        });
    }

    /**
     * Distribuye el monto del pago entre las ventas pendientes del cliente,
     * comenzando por la más antigua.
     */
    private function imputarPagoAutomaticamente(Pago $pago, Cliente $cliente): void
    {
        $montoDisponible = $pago->monto;

        // A. Obtener todas las ventas del cliente que NO están anuladas
        // Ordenamos por fecha para pagar lo más viejo primero.
        $ventasCliente = Venta::where('clienteID', $cliente->clienteID)
            ->where('anulada', false)
            ->orderBy('fecha_venta', 'asc')
            ->get();

        // B. Recorrer ventas y asignar dinero
        foreach ($ventasCliente as $venta) {
            if ($montoDisponible <= 0) break; // Se acabó la plata del pago

            // Usamos el accessor que creaste en el Modelo Venta
            $saldoPendiente = $venta->saldo_pendiente;

            if ($saldoPendiente > 0) {
                // Cuánto vamos a pagar de ESTA venta específica
                // Es el menor valor entre: lo que queda del pago O lo que se debe de la venta
                $montoAImputar = min($montoDisponible, $saldoPendiente);

                // Guardar la relación en la tabla pivote 'pago_venta_imputacion'
                $pago->ventasImputadas()->attach($venta->venta_id, [
                    'monto_imputado' => $montoAImputar
                ]);

                // Restar lo usado del dinero disponible
                $montoDisponible -= $montoAImputar;
            }
        }
        
        // Nota: Si sobra 'montoDisponible', queda como saldo a favor en la Cuenta Corriente (manejado en el paso 3),
        // pero no se imputa a ninguna venta específica porque no hay deuda.
    }
}