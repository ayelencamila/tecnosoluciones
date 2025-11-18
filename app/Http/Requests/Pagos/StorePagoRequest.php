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
            'clienteID.required' => 'Debe seleccionar un cliente.',
            'clienteID.exists' => 'El cliente seleccionado no existe.',
            'monto.required' => 'El monto del pago es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número válido.',
            'monto.min' => 'El monto del pago debe ser mayor a $0.01',
            'monto.max' => 'El monto del pago no puede exceder $999,999,999.00',
            'metodo_pago.required' => 'Debe seleccionar un método de pago.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres.',
        ];
    }
}