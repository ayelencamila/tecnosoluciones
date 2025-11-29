<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'stock_id' => ['required', 'exists:stock,stock_id'], // Valida que exista el registro de stock
            
            //  Validamos el ID de la tabla paramétrica, no un string fijo
            'tipo_movimiento_id' => ['required', 'exists:tipos_movimiento_stock,id'],
            
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_movimiento_id.required' => 'Seleccione un tipo de movimiento.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'motivo.required' => 'Debe indicar un motivo para el ajuste.',
        ];
    }
}