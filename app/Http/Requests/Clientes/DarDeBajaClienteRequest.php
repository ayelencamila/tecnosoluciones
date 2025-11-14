<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;

class DarDeBajaClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe ingresar un motivo para dar de baja el cliente.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}