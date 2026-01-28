<?php

namespace App\Services\Comprobantes;

use App\Models\Comprobante;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CU-32: Servicio para registrar comprobantes internos
 * 
 * Cada vez que se genera/imprime un comprobante desde ventas, pagos o reparaciones,
 * este servicio lo registra en la tabla centralizada de comprobantes.
 * 
 * Lineamientos:
 * - Larman (BCE): El servicio actúa como Control, coordinando Entidad y Boundary
 * - Kendall: Trazabilidad completa de documentos emitidos
 * - Elmasri: Relación polimórfica (tipo_entidad, entidad_id)
 */
class RegistrarComprobanteService
{
    /**
     * Códigos de tipo de comprobante (deben coincidir con tabla tipos_comprobante)
     */
    const TIPO_TICKET = 'TICKET';
    const TIPO_RECIBO_PAGO = 'RECIBO_PAGO';
    const TIPO_INGRESO_REPARACION = 'INGRESO_REPARACION';
    const TIPO_ENTREGA_REPARACION = 'ENTREGA_REPARACION';
    const TIPO_NOTA_CREDITO_INTERNA = 'NOTA_CREDITO_INTERNA';
    const TIPO_ORDEN_COMPRA = 'ORDEN_COMPRA';

    /**
     * Registra un comprobante de venta (Ticket)
     * 
     * @param int $ventaId ID de la venta
     * @param string $numeroComprobante Número de comprobante de la venta
     * @param int|null $usuarioId Usuario que emite (null = auth)
     * @return Comprobante
     */
    public function registrarComprobanteVenta(int $ventaId, string $numeroComprobante, ?int $usuarioId = null): Comprobante
    {
        return $this->registrar(
            tipoEntidad: 'App\Models\Venta',
            entidadId: $ventaId,
            tipoComprobanteCodigo: self::TIPO_TICKET,
            numeroCorrelativo: $numeroComprobante,
            usuarioId: $usuarioId,
            accionAuditoria: 'EMISION_COMPROBANTE_VENTA'
        );
    }

    /**
     * Registra un recibo de pago
     * 
     * @param int $pagoId ID del pago
     * @param string $numeroRecibo Número de recibo del pago
     * @param int|null $usuarioId Usuario que emite
     * @return Comprobante
     */
    public function registrarReciboPago(int $pagoId, string $numeroRecibo, ?int $usuarioId = null): Comprobante
    {
        return $this->registrar(
            tipoEntidad: 'App\Models\Pago',
            entidadId: $pagoId,
            tipoComprobanteCodigo: self::TIPO_RECIBO_PAGO,
            numeroCorrelativo: $numeroRecibo,
            usuarioId: $usuarioId,
            accionAuditoria: 'EMISION_RECIBO_PAGO'
        );
    }

    /**
     * Registra un comprobante de ingreso de reparación
     * 
     * @param int $reparacionId ID de la reparación
     * @param string $codigoReparacion Código de reparación
     * @param int|null $usuarioId Usuario que emite
     * @return Comprobante
     */
    public function registrarIngresoReparacion(int $reparacionId, string $codigoReparacion, ?int $usuarioId = null): Comprobante
    {
        return $this->registrar(
            tipoEntidad: 'App\Models\Reparacion',
            entidadId: $reparacionId,
            tipoComprobanteCodigo: self::TIPO_INGRESO_REPARACION,
            numeroCorrelativo: 'ING-' . $codigoReparacion,
            usuarioId: $usuarioId,
            accionAuditoria: 'EMISION_COMPROBANTE_INGRESO_REPARACION'
        );
    }

    /**
     * Registra un comprobante de entrega de reparación
     * 
     * @param int $reparacionId ID de la reparación
     * @param string $codigoReparacion Código de reparación
     * @param int|null $usuarioId Usuario que emite
     * @return Comprobante
     */
    public function registrarEntregaReparacion(int $reparacionId, string $codigoReparacion, ?int $usuarioId = null): Comprobante
    {
        return $this->registrar(
            tipoEntidad: 'App\Models\Reparacion',
            entidadId: $reparacionId,
            tipoComprobanteCodigo: self::TIPO_ENTREGA_REPARACION,
            numeroCorrelativo: 'ENT-' . $codigoReparacion,
            usuarioId: $usuarioId,
            accionAuditoria: 'EMISION_COMPROBANTE_ENTREGA_REPARACION'
        );
    }

    /**
     * Registra una nota de crédito interna (anulación de venta)
     * 
     * @param int $ventaId ID de la venta anulada
     * @param string $numeroComprobante Número del comprobante de anulación
     * @param int|null $usuarioId Usuario que emite
     * @return Comprobante
     */
    public function registrarNotaCreditoInterna(int $ventaId, string $numeroComprobante, ?int $usuarioId = null): Comprobante
    {
        return $this->registrar(
            tipoEntidad: 'App\Models\Venta',
            entidadId: $ventaId,
            tipoComprobanteCodigo: self::TIPO_NOTA_CREDITO_INTERNA,
            numeroCorrelativo: 'NC-' . $numeroComprobante,
            usuarioId: $usuarioId,
            accionAuditoria: 'EMISION_NOTA_CREDITO_INTERNA'
        );
    }

    /**
     * Método interno para registrar cualquier tipo de comprobante
     * 
     * @param string $tipoEntidad Clase del modelo relacionado
     * @param int $entidadId ID del registro relacionado
     * @param string $tipoComprobanteCodigo Código del tipo de comprobante
     * @param string $numeroCorrelativo Número único del comprobante
     * @param int|null $usuarioId Usuario que emite
     * @param string $accionAuditoria Acción para auditoría
     * @return Comprobante
     */
    protected function registrar(
        string $tipoEntidad,
        int $entidadId,
        string $tipoComprobanteCodigo,
        string $numeroCorrelativo,
        ?int $usuarioId,
        string $accionAuditoria
    ): Comprobante {
        return DB::transaction(function () use ($tipoEntidad, $entidadId, $tipoComprobanteCodigo, $numeroCorrelativo, $usuarioId, $accionAuditoria) {
            
            // Obtener el tipo_comprobante_id
            $tipoComprobante = DB::table('tipos_comprobante')
                ->where('codigo', $tipoComprobanteCodigo)
                ->first();

            if (!$tipoComprobante) {
                throw new \Exception("Tipo de comprobante no encontrado: {$tipoComprobanteCodigo}");
            }

            // Obtener el estado_comprobante_id para EMITIDO
            $estadoEmitido = DB::table('estados_comprobante')
                ->where('nombre', 'EMITIDO')
                ->value('estado_id');

            if (!$estadoEmitido) {
                throw new \Exception("Estado de comprobante EMITIDO no encontrado");
            }

            // Verificar si ya existe un comprobante para esta entidad y tipo
            $existente = Comprobante::where('tipo_entidad', $tipoEntidad)
                ->where('entidad_id', $entidadId)
                ->where('tipo_comprobante_id', $tipoComprobante->tipo_id)
                ->where('estado_comprobante_id', $estadoEmitido)
                ->first();

            if ($existente) {
                // Ya existe, devolver el existente
                Log::info("Comprobante ya existente: {$existente->numero_correlativo}");
                return $existente;
            }

            // Crear el comprobante
            $comprobante = Comprobante::create([
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'usuario_id' => $usuarioId ?? Auth::id(),
                'tipo_comprobante_id' => $tipoComprobante->tipo_id,
                'numero_correlativo' => $numeroCorrelativo,
                'fecha_emision' => now(),
                'estado_comprobante_id' => $estadoEmitido,
            ]);

            // Registrar auditoría
            try {
                Auditoria::create([
                    'accion' => $accionAuditoria,
                    'tablaAfectada' => 'comprobantes',
                    'registroID' => $comprobante->comprobante_id,
                    'valorNuevo' => json_encode([
                        'numero' => $comprobante->numero_correlativo,
                        'tipo' => $tipoComprobanteCodigo,
                        'entidad' => class_basename($tipoEntidad) . '#' . $entidadId,
                    ]),
                    'usuarioID' => $usuarioId ?? Auth::id(),
                    'ip' => request()->ip(),
                    'motivo' => 'Emisión de comprobante interno',
                ]);
            } catch (\Exception $e) {
                // CU-32 Excepción 9a: Error al registrar en historial no detiene la operación
                Log::warning("Error al registrar auditoría de comprobante: {$e->getMessage()}");
            }

            Log::info("Comprobante registrado: {$comprobante->numero_correlativo}");

            return $comprobante;
        });
    }

    /**
     * Registra la visualización de un comprobante en auditoría
     * 
     * @param Comprobante $comprobante
     * @param string $accion 'VER_PDF' o 'DESCARGAR_PDF'
     */
    public function registrarVisualizacion(Comprobante $comprobante, string $accion = 'VER_PDF'): void
    {
        try {
            Auditoria::create([
                'accion' => $accion,
                'tablaAfectada' => 'comprobantes',
                'registroID' => $comprobante->comprobante_id,
                'valorAnterior' => json_encode([
                    'numero' => $comprobante->numero_correlativo,
                ]),
                'usuarioID' => Auth::id(),
                'ip' => request()->ip(),
                'motivo' => 'Consulta de comprobante interno',
            ]);
        } catch (\Exception $e) {
            // CU-32 Excepción 9a: No detiene la operación
            Log::warning("Error al registrar visualización: {$e->getMessage()}");
        }
    }
}
