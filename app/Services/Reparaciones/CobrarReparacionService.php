<?php

namespace App\Services\Reparaciones;

use App\Models\Auditoria;
use App\Models\MedioPago;
use App\Models\Reparacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CobrarReparacionService
{
    /**
     * Registra el cobro de una reparación.
     *
     * Flujo:
     * 1. Si paga en efectivo/tarjeta/etc → cobro directo, estado_pago = 'pagado'
     * 2. Si paga con cuenta corriente → debita la CC del cliente, estado_pago = 'cuenta_corriente'
     * 3. Genera comprobante interno de cobro
     * 4. Registra auditoría
     */
    public function handle(Reparacion $reparacion, array $datos, int $userId): Reparacion
    {
        // Validaciones de negocio
        if ($reparacion->estado_pago === 'pagado') {
            throw new \DomainException('Esta reparación ya fue cobrada.');
        }

        if ($reparacion->anulada) {
            throw new \DomainException('No se puede cobrar una reparación anulada.');
        }

        $totalACobrar = $this->calcularTotalReparacion($reparacion);

        if ($totalACobrar <= 0) {
            throw new \DomainException('El total a cobrar es $0. Asegurate de cargar repuestos y/o mano de obra antes de cobrar.');
        }

        $medioPago = MedioPago::findOrFail($datos['medio_pago_id']);
        $esCuentaCorriente = str_contains(strtolower($medioPago->nombre), 'corriente');

        return DB::transaction(function () use ($reparacion, $userId, $totalACobrar, $medioPago, $esCuentaCorriente) {

            // --- Si es Cuenta Corriente: validar y debitar ---
            if ($esCuentaCorriente) {
                $this->procesarCuentaCorriente($reparacion, $totalACobrar, $userId);
                $estadoPago = 'cuenta_corriente';
            } else {
                $estadoPago = 'pagado';
            }

            // --- Actualizar reparación ---
            $reparacion->update([
                'estado_pago' => $estadoPago,
                'monto_cobrado' => $totalACobrar,
                'medio_pago_id' => $medioPago->medioPagoID,
                'fecha_cobro' => Carbon::now(),
                'cobrado_por' => $userId,
            ]);

            // Nota: El cobro NO cambia el estado de reparación.
            // La entrega (Reparado → Entregado) se confirma manualmente cuando
            // el cliente retira físicamente el equipo, via ActualizarReparacionService.
            // Esto respeta la separación de responsabilidades: pagar ≠ retirar.

            // --- Auditoría ---
            Auditoria::registrar(
                'cobro_reparacion',
                'reparaciones',
                $reparacion->reparacionID,
                null,
                [
                    'total_cobrado' => $totalACobrar,
                    'medio_pago' => $medioPago->nombre,
                    'es_cuenta_corriente' => $esCuentaCorriente,
                ],
                "Cobro de reparación {$reparacion->codigo_reparacion} por \${$totalACobrar}",
                null,
                $userId
            );

            Log::info("Cobro registrado para reparación {$reparacion->reparacionID}: \${$totalACobrar} via {$medioPago->nombre}");

            return $reparacion;
        });
    }

    /**
     * Calcula el total a cobrar de la reparación (repuestos + mano de obra)
     */
    private function calcularTotalReparacion(Reparacion $reparacion): float
    {
        // Si tiene total_final cargado, usarlo
        if ($reparacion->total_final && (float) $reparacion->total_final > 0) {
            return (float) $reparacion->total_final;
        }

        // Si no, calcularlo: repuestos + mano de obra
        $totalRepuestos = $reparacion->repuestos()->sum('subtotal');
        $manoDeObra = (float) ($reparacion->costo_mano_obra ?? 0);

        return $totalRepuestos + $manoDeObra;
    }

    /**
     * Procesa el cobro vía Cuenta Corriente: valida límite y debita
     */
    private function procesarCuentaCorriente(Reparacion $reparacion, float $monto, int $userId): void
    {
        $cliente = $reparacion->cliente()->first();

        if (! $cliente) {
            throw new \DomainException('No se encontró el cliente asociado a esta reparación.');
        }

        // Bloqueo pesimista real: re-consultamos la CC con lockForUpdate dentro de
        // la transacción para evitar condición de carrera al validar/debitar el saldo.
        $cc = $cliente->cuentaCorriente()->with('estadoCuentaCorriente')->lockForUpdate()->first();

        if (! $cc) {
            throw new \DomainException("El cliente {$cliente->nombre_completo} no tiene cuenta corriente habilitada. Elija otro medio de pago.");
        }

        $estadoCC = $cc->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        if ($estadoCC === 'Bloqueada') {
            throw new \DomainException("La cuenta corriente de {$cliente->nombre_completo} está BLOQUEADA. Solo se permite cobro en efectivo/tarjeta.");
        }

        // Validar límite de crédito
        $limiteCredito = $cc->getLimiteCreditoAplicable();
        $saldoActual = (float) $cc->saldo;

        if (($saldoActual + $monto) > $limiteCredito) {
            $disponible = max(0, $limiteCredito - $saldoActual);
            throw new \DomainException(
                "Excede límite de crédito. Límite: \${$limiteCredito}. Saldo actual: \${$saldoActual}. Disponible: \${$disponible}."
            );
        }

        // Debitar
        $diasGracia = $cc->diasGracia ?? 0;
        $fechaVencimiento = Carbon::now()->addDays($diasGracia);

        $cc->registrarDebito(
            $monto,
            "Cobro Reparación - {$reparacion->codigo_reparacion}",
            $fechaVencimiento,
            $reparacion->reparacionID,
            'reparaciones',
            $userId
        );

        Log::info("Deuda registrada en CC Cliente {$cliente->clienteID}: \${$monto} por reparación {$reparacion->codigo_reparacion}");
    }
}
