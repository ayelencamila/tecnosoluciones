<?php

namespace App\Services\Pagos;

use App\Events\PagoRegistrado;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Reparacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegistrarPagoService
{
    public function handle(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {
            
            $cliente = Cliente::with('cuentaCorriente')->findOrFail($data['clienteID']);

            // CORRECCIÓN: Usamos medioPagoID
            $pago = Pago::create([
                'clienteID'   => $cliente->clienteID,
                'user_id'     => $userId,
                'monto'       => $data['monto'],
                'medioPagoID' => $data['medioPagoID'],
                'fecha_pago'  => now(),
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Registrar crédito en cuenta corriente SOLO si el cliente la tiene (mayoristas)
            if ($cliente->cuentaCorriente) {
                $descripcion = "Pago Recibido - Recibo: {$pago->numero_recibo}";
                
                $cliente->cuentaCorriente->registrarCredito(
                    $pago->monto,
                    $descripcion,
                    $pago->pagoID,
                    'pagos',
                    $userId
                );
            }
            // Para clientes minoristas sin CC, el pago solo se registra y se imputa a ventas

            // CU-10 Paso 7: Imputación manual o automática
            // Si hay imputaciones manuales con contenido, usarlas. Si no, imputar automáticamente.
            if (!empty($data['imputaciones']) && is_array($data['imputaciones'])) {
                $this->imputarPagoManualmente($pago, $data['imputaciones']);
            } else {
                $this->imputarPagoAutomaticamente($pago, $cliente);
            }

            // CU-09 Paso 7: Disparar evento para verificar normalización
            event(new PagoRegistrado($pago, $userId));

            // CU-32: Registrar comprobante interno de pago (NO FISCAL)
            $tipoComprobanteId = \DB::table('tipos_comprobante')->where('codigo', 'RECIBO_PAGO')->value('tipo_id');
            $estadoEmitido = \DB::table('estados_comprobante')->where('nombre', 'EMITIDO')->value('estado_id');
            $numeroCorrelativo = \App\Models\Comprobante::generarNumeroCorrelativo('RECIBO_PAGO', 'P');

            \App\Models\Comprobante::create([
                'tipo_entidad' => $pago->getMorphClass(),
                'entidad_id' => $pago->pagoID,
                'usuario_id' => $userId,
                'tipo_comprobante_id' => $tipoComprobanteId,
                'numero_correlativo' => $numeroCorrelativo,
                'fecha_emision' => now(),
                'estado_comprobante_id' => $estadoEmitido,
            ]);

            // CU-10 Paso 13: Registrar en historial de operaciones
            \App\Models\Auditoria::registrar(
                \App\Models\Auditoria::ACCION_REGISTRAR_PAGO,
                'pagos',
                $pago->pagoID,
                null,
                [
                    'numero_recibo' => $pago->numero_recibo,
                    'monto' => $pago->monto,
                    'clienteID' => $pago->clienteID,
                    'medioPagoID' => $pago->medioPagoID,
                ],
                "Pago recibido de cliente ID {$cliente->clienteID}",
                "Monto: \${$pago->monto} - Recibo: {$pago->numero_recibo}",
                $userId
            );

            Log::info("Pago registrado e imputado: ID {$pago->pagoID}");

            return $pago;
        });
    }

    private function imputarPagoAutomaticamente(Pago $pago, Cliente $cliente): void
    {
        $montoDisponible = $pago->monto;

        // Primero: ventas activas (no anuladas) ordenadas por fecha
        $ventasCliente = Venta::where('clienteID', $cliente->clienteID)
            ->whereHas('estado', fn($q) => $q->where('nombreEstado', '!=', 'Anulada'))
            ->orderBy('fecha_venta', 'asc')
            ->get();

        foreach ($ventasCliente as $venta) {
            if ($montoDisponible <= 0) break;

            $saldoPendiente = $venta->saldo_pendiente;

            if ($saldoPendiente > 0) {
                $montoAImputar = min($montoDisponible, $saldoPendiente);

                $pago->ventasImputadas()->attach($venta->venta_id, [
                    'monto_imputado' => $montoAImputar
                ]);

                $montoDisponible -= $montoAImputar;
            }
        }

        // Segundo: reparaciones cobradas a cuenta corriente, ordenadas por fecha de cobro
        if ($montoDisponible > 0) {
            $reparacionesCliente = Reparacion::where('clienteID', $cliente->clienteID)
                ->where('estado_pago', 'cuenta_corriente')
                ->where('anulada', false)
                ->with('pagosImputados')
                ->orderBy('fecha_cobro', 'asc')
                ->get();

            foreach ($reparacionesCliente as $reparacion) {
                if ($montoDisponible <= 0) break;

                $saldoPendiente = $reparacion->saldo_pendiente;

                if ($saldoPendiente > 0) {
                    $montoAImputar = min($montoDisponible, $saldoPendiente);

                    $pago->reparacionesImputadas()->attach($reparacion->reparacionID, [
                        'monto_imputado' => $montoAImputar
                    ]);

                    $montoDisponible -= $montoAImputar;
                }
            }
        }
    }

    /**
     * Imputa el pago manualmente según las instrucciones del usuario (CU-10 Paso 7)
     * Soporta imputaciones a ventas y reparaciones
     */
    private function imputarPagoManualmente(Pago $pago, array $imputaciones): void
    {
        foreach ($imputaciones as $imputacion) {
            $tipo = $imputacion['tipo'] ?? 'venta';

            if ($tipo === 'reparacion' && !empty($imputacion['reparacion_id'])) {
                $pago->reparacionesImputadas()->attach($imputacion['reparacion_id'], [
                    'monto_imputado' => $imputacion['monto_imputado']
                ]);
            } else {
                $pago->ventasImputadas()->attach($imputacion['venta_id'], [
                    'monto_imputado' => $imputacion['monto_imputado']
                ]);
            }
        }
    }
}