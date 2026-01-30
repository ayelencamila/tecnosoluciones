<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\OfertaCompra;

/**
 * Request de validación para generar Orden de Compra (CU-22)
 * 
 * Valida:
 * - oferta_id: debe existir y estar en estado "Elegida"
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
        // El rol en la base de datos es 'administrador' (minúscula)
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
            'oferta_id' => [
                'required',
                'integer',
                'exists:ofertas_compra,id',
                function ($attribute, $value, $fail) {
                    $oferta = OfertaCompra::with('estado')->find($value);
                    
                    if (!$oferta) {
                        $fail('La oferta seleccionada no existe.');
                        return;
                    }
                    
                    // Usar el método del modelo para verificar el estado
                    if (!$oferta->tieneEstado('Elegida')) {
                        $fail('Solo se puede generar OC de ofertas en estado "Elegida". Estado actual: ' . $oferta->estado->nombre);
                    }
                    
                    if ($oferta->ordenesCompra()->exists()) {
                        $fail('Esta oferta ya tiene una Orden de Compra asociada.');
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
            'oferta_id.required' => 'Debe seleccionar una oferta.',
            'oferta_id.exists' => 'La oferta seleccionada no existe.',
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
            'oferta_id' => 'oferta',
            'motivo' => 'motivo de generación',
        ];
    }
}
