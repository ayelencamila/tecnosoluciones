<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Request para validación de datos de usuario.
 * Implementa validaciones según los casos de uso de gestión de usuarios.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'exists:roles,rol_id'],
        ];

        // En creación, validamos email y password
        if ($this->isMethod('POST')) {
            $rules['email'] = [
                'required', 
                'string', 
                'email', 
                'max:255', 
                'unique:users,email'
            ];
            $rules['password'] = [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ];
        }

        // En actualización, email no se puede modificar (no lo validamos)
        // y password es opcional (solo si se quiere cambiar)

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol_id.required' => 'Debe seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'telefono' => 'teléfono',
            'rol_id' => 'rol',
        ];
    }
}
