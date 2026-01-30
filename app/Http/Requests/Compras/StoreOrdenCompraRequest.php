<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CotizacionProveedor;

/**
 * Request de validación para generar Orden de Compra (CU-22)
 * 
 * MODELO SIMPLIFICADO (sin tabla ofertas_compra):
 * Valida:
 * - cotizacion_id: debe existir y estar marcada como elegida
 * - motivo: requerido (motivo de generación de la OC para auditoría)
 */
class StoreOrdenCompraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores pueden generar OC
        return $this->user()->role === 'administrador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cotizacion_id' => [
                'required',
                'integer',
                'exists:cotizaciones_proveedores,id',
                function ($attribute, $value, $fail) {
                    $cotizacion = CotizacionProveedor::find($value);
                    
                    if (!$cotizacion) {
                        $fail('La cotización seleccionada no existe.');
                        return;
                    }
                    
                    if (!$cotizacion->elegida) {
                        $fail('Solo se puede generar OC de cotizaciones elegidas.');
                    }
                    
                    if ($cotizacion->ordenCompra()->exists()) {
                        $fail('Esta cotización ya tiene una Orden de Compra asociada.');
                    }
                },
            ],
            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cotizacion_id.required' => 'Debe seleccionar una cotización.',
            'cotizacion_id.exists' => 'La cotización seleccionada no existe.',
            'motivo.required' => 'El motivo es obligatorio para generar la Orden de Compra.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede exceder 1000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cotizacion_id' => 'cotización',
            'motivo' => 'motivo de generación',
        ];
    }
}
