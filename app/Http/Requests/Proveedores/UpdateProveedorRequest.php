<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor') ? $this->route('proveedor')->id : null;

        return [
            'razon_social' => ['required', 'string', 'max:100', Rule::unique('proveedores')->ignore($proveedorId)],
            'cuit' => ['nullable', 'digits:11', Rule::unique('proveedores')->ignore($proveedorId)], // Opcional
            'email' => ['nullable', 'email', 'max:100'], // Opcional
            'telefono' => ['nullable', 'string', 'max:20'],
            'forma_pago_preferida' => ['nullable', 'string', 'max:50'],
            'plazo_entrega_estimado' => ['nullable', 'integer', 'min:0'],
            
            // Dirección
            'calle' => ['required', 'string', 'max:100'],
            'altura' => ['nullable', 'string', 'max:20'], // Opcional
            'localidad_id' => ['required', 'exists:localidades,localidadID'],

            // Kendall CU-17: Motivo de modificación OBLIGATORIO
            'motivo' => ['required', 'string', 'min:5', 'max:255'], 
        ];
    }
}