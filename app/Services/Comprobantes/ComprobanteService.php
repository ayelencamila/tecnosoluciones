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
}
