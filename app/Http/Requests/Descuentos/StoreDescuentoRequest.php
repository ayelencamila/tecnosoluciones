<?php

namespace App\Http\Requests\Descuentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDescuentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pre-condición: Solo admin (se puede refinar con Policies)
        return auth()->check(); 
    }

    public function rules(): array
    {
        return [
            // Unicidad de código (Excepción 6a)
            'codigo' => [
                'required', 
                'string', 
                'max:50', 
                'uppercase', 
                Rule::unique('descuentos', 'codigo')->ignore($this->descuento)
            ],
            'descripcion' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor' => ['required', 'numeric', 'min:0.01'],
            // Validación de Fechas (Paso 6)
            'valido_desde' => ['required', 'date'], // Puede ser hoy o futuro
            'valido_hasta' => ['nullable', 'date', 'after_or_equal:valido_desde'], // Coherencia temporal
            'aplicabilidad' => ['required', Rule::in(['total', 'item', 'ambos'])],
            'activo' => ['boolean'],
        ];
    }

    public function messages()
    {
        return [
            'codigo.unique' => 'Ya existe un descuento con este código (Excepción 6a).',
            'valido_hasta.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
        ];
    }
}