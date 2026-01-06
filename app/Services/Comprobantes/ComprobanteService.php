<?php

namespace App\Services\Comprobantes;

use App\Models\Pago;
use App\Models\Configuracion;

/**
 * Servicio de Control (BCE) para preparar datos de comprobantes
 * Siguiendo lineamientos de Kendall: recolecta información de entidades
 * y la estructura para la salida (Boundary/Vista)
 */
class ComprobanteService
{
    /**
     * Prepara los datos del recibo de pago siguiendo lineamientos de Kendall
     * 
     * Objetivos de Kendall aplicados:
     * 1. Servir al propósito previsto: Constancia de pago recibido
     * 2. Ajustar al usuario: Cliente necesita ver qué pagó y cuánto
     * 3. Cantidad adecuada: Solo datos relevantes, sin saturar
     * 4. Proveer a tiempo: Se genera inmediatamente después del registro
     * 
     * @param Pago $pago Entidad con la información del pago
     * @return array Datos estructurados para la vista
     */
    public function prepararDatosReciboPago(Pago $pago): array
    {
        // Información CONSTANTE (datos de la empresa)
        $datosEmpresa = [
            'nombre' => Configuracion::get('nombre_empresa', 'TecnoSoluciones'),
            'direccion' => Configuracion::get('direccion_empresa', ''),
            'telefono' => Configuracion::get('telefono_empresa', ''),
            'email' => Configuracion::get('email_empresa', ''),
            'cuit' => Configuracion::get('cuit_empresa', ''),
        ];

        // Información VARIABLE (datos del pago)
        $pago->load(['cliente', 'cajero', 'medioPago', 'ventasImputadas']);
        
        // Calcular totales para imputación y anticipo
        $totalImputado = $pago->ventasImputadas->sum('pivot.monto_imputado');
        $anticipoGenerado = max(0, $pago->monto - $totalImputado);
        
        // Preparar detalles de imputación (Kendall: evitar códigos confusos)
        $imputaciones = $pago->ventasImputadas->map(function($venta) {
            return [
                'numero_comprobante' => $venta->numero_comprobante,
                'fecha_venta' => $venta->fecha_venta->format('d/m/Y'),
                'total_venta' => $venta->total,
                'monto_imputado' => $venta->pivot->monto_imputado,
            ];
        })->toArray();

        return [
            // Información CONSTANTE
            'empresa' => $datosEmpresa,
            
            // Información VARIABLE - Encabezado del Recibo
            'recibo' => [
                'numero' => $pago->numero_recibo,
                'fecha' => $pago->fecha_pago->format('d/m/Y H:i'),
                'estado' => $pago->anulado ? 'ANULADO' : 'VÁLIDO',
            ],
            
            // Cliente (Kendall: información comprensible, no solo IDs)
            'cliente' => [
                'nombre_completo' => $pago->cliente 
                    ? "{$pago->cliente->apellido}, {$pago->cliente->nombre}"
                    : 'Consumidor Final',
                'dni' => $pago->cliente->DNI ?? '',
                'domicilio' => $pago->cliente->domicilio ?? '',
            ],
            
            // Detalles del Pago
            'pago' => [
                'monto_total' => $pago->monto,
                'medio_pago' => $pago->medioPago->nombre ?? 'Desconocido',
                'observaciones' => $pago->observaciones,
                'cajero' => $pago->cajero->name ?? 'Sistema',
            ],
            
            // Imputaciones (Kendall: contenido del informe con detalles necesarios)
            'imputaciones' => $imputaciones,
            'totales' => [
                'total_imputado' => $totalImputado,
                'anticipo_generado' => $anticipoGenerado,
            ],
            
            // Metadata
            'es_anulado' => $pago->anulado,
            'fecha_emision' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Prepara los datos del comprobante de venta siguiendo lineamientos de Kendall
     * 
     * Objetivos de Kendall aplicados:
     * 1. Servir al propósito: Constancia de compra realizada
     * 2. Ajustar al usuario: Cliente necesita ver qué compró y cuánto pagó
     * 3. Cantidad adecuada: Detalle de productos, precios y totales
     * 4. Proveer a tiempo: Se genera inmediatamente después de la venta
     * 
     * @param \App\Models\Venta $venta Entidad con la información de la venta
     * @return array Datos estructurados para la vista
     */
    public function prepararDatosComprobanteVenta($venta): array
    {
        // Información CONSTANTE (datos de la empresa)
        $datosEmpresa = [
            'nombre' => Configuracion::get('nombre_empresa', 'TecnoSoluciones'),
            'direccion' => Configuracion::get('direccion_empresa', ''),
            'telefono' => Configuracion::get('telefono_empresa', ''),
            'email' => Configuracion::get('email_empresa', ''),
            'cuit' => Configuracion::get('cuit_empresa', ''),
        ];

        // Información VARIABLE (datos de la venta)
        $venta->load(['cliente', 'vendedor', 'medioPago', 'estado', 'detalles.producto', 'descuentos']);
        
        // Preparar detalles de productos (Kendall: evitar códigos confusos)
        $detalles = $venta->detalles->map(function($detalle) {
            return [
                'descripcion' => $detalle->producto->nombre ?? 'Producto',
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal_bruto' => $detalle->subtotal,
                'descuento' => $detalle->descuento_item,
                'subtotal_neto' => $detalle->subtotal_neto,
            ];
        })->toArray();

        // Preparar descuentos aplicados
        $descuentosAplicados = $venta->descuentos->map(function($descuento) {
            return [
                'nombre' => $descuento->nombre,
                'monto' => $descuento->pivot->monto_aplicado ?? 0,
            ];
        })->toArray();

        // Estado de la venta
        $estadoVenta = $venta->estado->nombreEstado ?? 'Desconocido';
        $esAnulada = $estadoVenta === 'Anulada';

        return [
            // Información CONSTANTE
            'empresa' => $datosEmpresa,
            
            // Información VARIABLE - Encabezado del Comprobante
            'comprobante' => [
                'numero' => $venta->numero_comprobante,
                'fecha' => $venta->fecha_venta->format('d/m/Y H:i'),
                'estado' => $estadoVenta,
            ],
            
            // Cliente (Kendall: información comprensible)
            'cliente' => [
                'nombre_completo' => $venta->cliente 
                    ? "{$venta->cliente->apellido}, {$venta->cliente->nombre}"
                    : 'Consumidor Final',
                'dni' => $venta->cliente->DNI ?? '',
                'tipo' => $venta->cliente->tipoCliente->nombreTipo ?? '',
            ],
            
            // Vendedor
            'vendedor' => $venta->vendedor->name ?? 'Sistema',
            
            // Detalles (Kendall: contenido del informe con detalles necesarios)
            'detalles' => $detalles,
            
            // Descuentos globales
            'descuentos_globales' => $descuentosAplicados,
            
            // Totales (Kendall: subtotales útiles con alineación correcta)
            'totales' => [
                'subtotal' => $venta->subtotal,
                'total_descuentos' => $venta->total_descuentos,
                'total_final' => $venta->total,
            ],
            
            // Método de pago
            'medio_pago' => $venta->medioPago->nombre ?? 'Desconocido',
            
            // Observaciones
            'observaciones' => $venta->observaciones,
            'motivo_anulacion' => $venta->motivo_anulacion,
            
            // Metadata
            'es_anulada' => $esAnulada,
            'fecha_emision' => now()->format('d/m/Y H:i:s'),
        ];
    }
}
