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
            
            // Validación CU-10 Excepción 5b: El cliente DEBE tener Cuenta Corriente
            if (!$cliente->cuentaCorriente) {
                throw new Exception("El cliente {$cliente->nombre} {$cliente->apellido} no tiene cuenta corriente activa. Solo clientes mayoristas pueden registrar pagos.");
            }

            // Validación CU-10 Excepción 7a: El monto no puede exceder el saldo adeudado
            if ($data['monto'] > $cliente->cuentaCorriente->saldo) {
                $saldoFormateado = number_format($cliente->cuentaCorriente->saldo, 2, ',', '.');
                $montoFormateado = number_format($data['monto'], 2, ',', '.');
                throw new Exception("El monto del pago (\${$montoFormateado}) excede el saldo adeudado (\${$saldoFormateado}). No se permiten pagos adelantados.");
            }

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
            $descripcion = "Pago Recibido - Recibo: {$pago->numero_recibo}";
            
            $cliente->cuentaCorriente->registrarCredito(
                $pago->monto,
                $descripcion,
                $pago->pago_id,
                'pagos',
                $userId
            );

            // Lógica Extra: Si paga todo, desbloquear automáticamente
            if ($cliente->cuentaCorriente->saldo <= 0 && $cliente->cuentaCorriente->estaBloqueada()) {
                $cliente->cuentaCorriente->desbloquear("Desbloqueo automático por pago total (Recibo: {$pago->numero_recibo})", $userId);
            }

            Log::info("Pago registrado exitosamente: ID {$pago->pago_id} - Cliente {$cliente->clienteID}");

            return $pago;
        });
    }
}