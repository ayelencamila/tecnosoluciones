<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_id' => 'required|exists:stock,stock_id',
            'tipoMovimiento' => ['required', Rule::in(['ENTRADA', 'SALIDA', 'AJUSTE'])],
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|min:5|max:255',
        ];
    }
}