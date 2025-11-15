<?php

namespace App\Services\Pagos;

use App\Models\Pago;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegistrarPagoService
{
    /**
     * Registra un pago y actualiza la cuenta corriente.
     * (CU-10)
     */
    public function handle(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {
            
            // 1. Validar Cliente
            $cliente = Cliente::with('cuentaCorriente')->findOrFail($data['clienteID']);

            // 2. Crear Registro de Pago (El comprobante físico)
            $pago = Pago::create([
                'clienteID' => $cliente->clienteID,
                'user_id'   => $userId,
                'monto'     => $data['monto'],
                'metodo_pago' => $data['metodo_pago'],
                'fecha_pago'  => now(),
                'observaciones' => $data['observaciones'] ?? null,
                // 'numero_recibo' se genera solo en el boot del modelo
            ]);

            // 3. Actualizar Cuenta Corriente (CU-10 Paso 11)
            if ($cliente->cuentaCorriente) {
                $descripcion = "Pago Recibido - Recibo: {$pago->numero_recibo}";
                
                $cliente->cuentaCorriente->registrarCredito(
                    $pago->monto,
                    $descripcion,
                    $pago->pago_id,
                    'pagos',
                    $userId
                );

                // Lógica Extra: Si paga todo, intentamos desbloquear automáticamente
                if ($cliente->cuentaCorriente->saldo <= 0 && $cliente->cuentaCorriente->estaBloqueada()) {
                    $cliente->cuentaCorriente->desbloquear("Desbloqueo automático por pago total (Recibo: {$pago->numero_recibo})", $userId);
                }
            } else {
                // Si es un cliente minorista sin CC, el pago queda registrado solo como historial de caja.
                // (O podrías lanzar excepción si tu regla de negocio exige CC para pagar).
            }

            Log::info("Pago registrado exitosamente: ID {$pago->pago_id} - Cliente {$cliente->clienteID}");

            return $pago;
        });
    }
}