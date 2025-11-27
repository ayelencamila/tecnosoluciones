<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // Solo usuarios logueados
    }

    public function rules(): array
    {
        return [
            'clienteID'   => ['required', 'integer', 'exists:clientes,clienteID'],
            'monto'       => ['required', 'numeric', 'min:0.01', 'max:99999999.99'], // Evitar negativos
            'medioPagoID' => ['required', 'exists:medios_pago,medioPagoID'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'clienteID.required' => 'Debe seleccionar un cliente.',
            'monto.min' => 'El monto del pago debe ser mayor a 0.',
        ];
    }
}