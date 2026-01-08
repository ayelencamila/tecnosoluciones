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
            // Recuperamos los productos desde la Solicitud de Cotización original.
            // (Asumimos que la oferta respeta los ítems pedidos en la solicitud)
            $solicitud = $oferta->solicitud;
            
            if (!$solicitud || empty($solicitud->detalle_productos)) {
                throw new Exception("La oferta no tiene una solicitud válida o detalles de productos para copiar.");
            }

            // El detalle_productos en Solicitud es un array JSON: [['producto_id' => 1, 'cantidad' => 10], ...]
            $itemsSolicitados = $solicitud->detalle_productos;
            
            // Lógica de distribución de precio (Design Decision)
            // Como la oferta suele tener un "Precio Total", calculamos un unitario promedio si no está desglosado,
            // o usamos el precio total de la oferta distribuido proporcionalmente.
            // Para esta versión, usaremos una lógica simple: Precio 0 (a definir) o prorrateo.
            
            // Calcular si podemos prorratear (Evitar división por cero)
            $totalCantidad = collect($itemsSolicitados)->sum('cantidad');
            $precioPromedio = $totalCantidad > 0 ? ($oferta->precio_total / $totalCantidad) : 0;

            foreach ($itemsSolicitados as $item) {
                $orden->detalles()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad_pedida' => $item['cantidad'],
                    'cantidad_recibida' => 0, // Inicializa en 0 para el CU-23
                    
                    // Aquí asignamos el precio unitario. 
                    // En un sistema real complejo, la Oferta debería tener su propio JSON de precios unitarios.
                    // Por ahora, usamos el precio promedio para cuadrar el total.
                    'precio_unitario_pactado' => round($precioPromedio, 2), 
                ]);
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