<?php

namespace App\Http\Requests\Plataforma;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la edición de los datos de una empresa cliente.
 *
 * Deliberadamente NO permite cambiar `activa` (eso tiene su propia acción
 * auditada, suspender/reactivar) ni `slug`. El slug identifica a la empresa
 * en claves de caché y en los parámetros de configuración, así que cambiarlo
 * dejaría referencias colgadas; si alguna vez hay que renombrarlo, tiene que
 * ser una operación aparte que arrastre esas referencias.
 */
class ActualizarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya lo controla el middleware `super`.
        return $this->user()?->esSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $empresaId = $this->route('empresa')?->id;

        return [
            'nombre'    => ['required', 'string', 'max:150'],
            // El CUIT identifica fiscalmente a la empresa: dos clientes con el
            // mismo CUIT serían el mismo contribuyente cargado dos veces.
            'cuit'      => ['nullable', 'string', 'max:20', Rule::unique('empresas', 'cuit')->ignore($empresaId)],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'cuit.unique'     => 'Ya hay otra empresa registrada con ese CUIT.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la empresa',
        ];
    }
}
