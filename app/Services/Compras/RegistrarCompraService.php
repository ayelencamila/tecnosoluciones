<?php

namespace App\Services\Compras;

use App\Models\OrdenCompra;
use App\Models\OfertaCompra;
use App\Models\Auditoria;
use App\Models\SolicitudCotizacion;
use Illuminate\Support\Facades\DB;
use Exception;

class RegistrarCompraService
{
    /**
     * Ejecuta el flujo CU-21 (Confirmar Oferta) -> CU-22 (Generar Orden de Compra).
     * * Este servicio es el "Cerebro" que garantiza la integridad entre la oferta elegida
     * y la orden generada, asegurando que no se dupliquen y manteniendo el rastro.
     *
     * @param int $ofertaId ID de la oferta seleccionada
     * @param int $usuarioId ID del administrador que realiza la acción
     * @param string $observaciones Motivo o nota de aprobación (Requerido por Kendall)
     * @return OrdenCompra
     * @throws Exception
     */
    public function ejecutar(int $ofertaId, int $usuarioId, string $observaciones): OrdenCompra
    {
        return DB::transaction(function () use ($ofertaId, $usuarioId, $observaciones) {
            
            // 1. Validar y Bloquear la Oferta (Pessimistic Locking - Elmasri)
            // Evita que dos admins aprueben la misma oferta simultáneamente.
            $oferta = OfertaCompra::with(['solicitud', 'proveedor'])
                ->lockForUpdate()
                ->findOrFail($ofertaId);

            // Validaciones de Reglas de Negocio (Larman)
            if ($oferta->ordenesCompra()->exists()) {
                throw new Exception("Esta oferta ya ha sido procesada y tiene una Orden de Compra asociada (Integridad 1:1).");
            }

            if ($oferta->estado === OfertaCompra::ESTADO_RECHAZADA) {
                throw new Exception("No se puede generar una orden de una oferta rechazada.");
            }

            // 2. Transición de Estado de la Oferta (CU-21)
            // Si la oferta no estaba elegida, la elegimos ahora implícitamente
            if ($oferta->estado !== OfertaCompra::ESTADO_ELEGIDA) {
                $oferta->elegir();
            }

            // 3. Generar Cabecera de Orden de Compra (CU-22)
            $orden = OrdenCompra::create([
                'numero_oc'    => OrdenCompra::generarNumeroOC(), // Tu método blindado contra race conditions
                'proveedor_id' => $oferta->proveedor_id,
                'oferta_id'    => $oferta->id, // Trazabilidad
                'user_id'      => $usuarioId,
                'total'        => $oferta->precio_total, // El precio se congela según la oferta
                'estado'       => OrdenCompra::ESTADO_BORRADOR, // Inicia en Borrador para revisión final antes de enviar
                'observaciones'=> $observaciones
            ]);

            // 4. Generar Detalles de la Orden
            // Recuperamos los productos desde los detalles de la OFERTA (1FN - Normalizado)
            // La oferta tiene precios unitarios reales cotizados por el proveedor
            $detallesOferta = $oferta->detalles;
            
            if ($detallesOferta->isEmpty()) {
                // Fallback: Si la oferta no tiene detalles, intentamos desde la solicitud
                $solicitud = $oferta->solicitud;
                
                if (!$solicitud || $solicitud->detalles->isEmpty()) {
                    throw new Exception("La oferta no tiene detalles de productos para generar la orden.");
                }
                
                // Usar detalles de la solicitud con precio promedio (legacy/fallback)
                $totalCantidad = $solicitud->detalles->sum('cantidad_sugerida');
                $precioPromedio = $totalCantidad > 0 ? ($oferta->precio_total / $totalCantidad) : 0;
                
                foreach ($solicitud->detalles as $detalleSolicitud) {
                    $orden->detalles()->create([
                        'producto_id' => $detalleSolicitud->producto_id,
                        'cantidad_pedida' => $detalleSolicitud->cantidad_sugerida,
                        'cantidad_recibida' => 0,
                        'precio_unitario' => round($precioPromedio, 2),
                    ]);
                }
            } else {
                // Caso ideal: Usar detalles de la oferta con precios unitarios reales
                foreach ($detallesOferta as $detalleOferta) {
                    $orden->detalles()->create([
                        'producto_id' => $detalleOferta->producto_id,
                        'cantidad_pedida' => $detalleOferta->cantidad_ofrecida,
                        'cantidad_recibida' => 0,
                        'precio_unitario' => $detalleOferta->precio_unitario,
                    ]);
                }
            }

            // 5. Actualizar estado final de la Oferta
            $oferta->marcarProcesada();

            // 6. Auditoría Cruzada (Kendall)
            Auditoria::registrar(
                accion: Auditoria::ACCION_GENERAR_ORDEN_COMPRA,
                tabla: 'ordenes_compra',
                registroId: $orden->id,
                motivo: $observaciones,
                detalles: "OC generada desde Oferta #{$oferta->id}. Proveedor: {$oferta->proveedor->razon_social}",
                usuarioId: $usuarioId
            );

            return $orden;
        });
    }
}