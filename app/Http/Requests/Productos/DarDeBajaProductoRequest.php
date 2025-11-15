<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

class DarDeBajaProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La policy del controlador se encargará
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
            'motivo.required' => 'Debe ingresar un motivo para dar de baja el producto.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
