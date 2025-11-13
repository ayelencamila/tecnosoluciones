<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La seguridad la maneja el Controller/Policy
    }

    public function rules(): array
    {
        return [
            'clienteID' => ['required', 'integer', 'exists:clientes,clienteID'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999999'], // Evita pagos negativos o irreales
            'metodo_pago' => ['required', 'string', Rule::in(['efectivo', 'transferencia', 'cheque', 'tarjeta'])],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'monto.min' => 'El monto del pago debe ser mayor a 0.',
            'clienteID.exists' => 'El cliente seleccionado no existe.',
        ];
    }
}