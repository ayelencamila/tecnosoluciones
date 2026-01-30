<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asumimos que el middleware de rol ya protegió la ruta
    }

    public function rules(): array
    {
        return [
            // Identificación
            'razon_social' => ['required', 'string', 'max:100', 'unique:proveedores,razon_social'],
            'cuit' => ['nullable', 'digits:11', 'unique:proveedores,cuit'], // Opcional
            'email' => ['nullable', 'email', 'max:100'], // Opcional
            'telefono' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            
            // Condiciones Comerciales
            'forma_pago_preferida' => ['nullable', 'string', 'max:50'],
            'plazo_entrega_estimado' => ['nullable', 'integer', 'min:0'],
            
            // Dirección (Relación con tabla direcciones)
            'calle' => ['required', 'string', 'max:100'],
            'altura' => ['nullable', 'string', 'max:20'], // Opcional
            'localidad_id' => ['required', 'exists:localidades,localidadID'],
        ];
    }

    public function messages(): array
    {
        // Kendall: Mensajes claros para el usuario
        return [
            'cuit.digits' => 'El CUIT debe tener exactamente 11 números sin guiones.',
            'cuit.unique' => 'Ya existe un proveedor con este CUIT.',
            'razon_social.unique' => 'Ya existe un proveedor con esta Razón Social.',
        ];
    }
}