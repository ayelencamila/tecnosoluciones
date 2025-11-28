<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clienteID'   => ['required', 'integer', 'exists:clientes,clienteID'],
            'monto'       => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'medioPagoID' => ['required', 'exists:medios_pago,medioPagoID'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'clienteID.required' => 'Debe seleccionar un cliente.',
            'medioPagoID.required' => 'Debe seleccionar un método de pago válido.',
            'monto.min' => 'El monto del pago debe ser mayor a 0.',
        ];
    }
}