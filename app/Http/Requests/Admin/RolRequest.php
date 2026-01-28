<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para validación de datos de rol.
 * Implementa validaciones según los casos de uso de gestión de roles.
 */
class RolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware
    }

    public function rules(): array
    {
        $rolId = $this->route('role')?->rol_id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'nombre')->ignore($rolId, 'rol_id'),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.max' => 'El nombre del rol no puede exceder los 50 caracteres.',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
            'descripcion.max' => 'La descripción no puede exceder los 255 caracteres.',
            'permisos.array' => 'Los permisos deben ser un listado válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del rol',
            'descripcion' => 'descripción',
            'permisos' => 'permisos',
        ];
    }
}
