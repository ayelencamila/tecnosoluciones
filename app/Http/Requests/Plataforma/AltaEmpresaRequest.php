<?php

namespace App\Http\Requests\Plataforma;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del alta de un cliente nuevo (empresa + su administrador).
 *
 * `users.email` es UNIQUE global por decision de diseño: una persona = un
 * mail en todo el sistema, no puede repetirse entre empresas. De ahi que la
 * regla de unicidad del email del administrador no se acote por empresa.
 */
class AltaEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo controla el middleware `super`.
        return $this->user()?->esSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // Datos de la empresa
            'nombre'    => ['required', 'string', 'max:150'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],

            // Usuario administrador inicial
            'admin_name'     => ['required', 'string', 'max:255'],
            'admin_email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_telefono' => ['nullable', 'string', 'max:20'],
            'admin_password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'         => 'El nombre de la empresa es obligatorio.',
            'admin_name.required'     => 'El nombre del administrador es obligatorio.',
            'admin_email.required'    => 'El email del administrador es obligatorio.',
            'admin_email.unique'      => 'Ese email ya está registrado en el sistema.',
            'admin_password.required' => 'La contraseña del administrador es obligatoria.',
            'admin_password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre'         => 'nombre de la empresa',
            'admin_name'     => 'nombre del administrador',
            'admin_email'    => 'email del administrador',
            'admin_password' => 'contraseña del administrador',
        ];
    }
}
