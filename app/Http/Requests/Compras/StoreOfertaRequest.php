<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autenticación manejada por middleware/rutas
    }

    public function rules(): array
    {
        return [
            // Cabecera
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'solicitud_id' => ['nullable', 'exists:solicitudes_cotizacion,id'],
            'fecha_recepcion' => ['required', 'date', 'before_or_equal:now'],
            'validez_hasta' => ['nullable', 'date', 'after_or_equal:fecha_recepcion'],
            'observaciones' => ['required', 'string', 'max:1000'], // CU-21 Paso 7a: Motivo obligatorio
            
            // Archivo (Evidencia)
            'archivo_adjunto' => [
                'nullable', 
                'file', 
                'mimes:pdf,jpg,jpeg,png,webp', 
                'max:10240' // Máx 10MB
            ],

            // Detalle de Productos (Array dinámico)
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            
            // Integridad de datos numéricos (Kendall)
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            
            // Condiciones logísticas
            'items.*.disponibilidad_inmediata' => ['boolean'],
            'items.*.dias_entrega' => ['required_if:items.*.disponibilidad_inmediata,false', 'integer', 'min:0'],
            'items.*.observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'items.required' => 'La oferta debe contener al menos un producto.',
            'items.*.precio_unitario.min' => 'El precio no puede ser negativo.',
            'observaciones.required' => 'Debe ingresar un motivo u observación para registrar la oferta.',
        ];
    }
}