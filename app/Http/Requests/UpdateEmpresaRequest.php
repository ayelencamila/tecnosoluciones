<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para edicion de la empresa (tenant) activa.
 * El logo es opcional. Si se envia, debe ser una imagen <= 2MB.
 */
class UpdateEmpresaRequest extends FormRequest
{
    /**
     * Se autoriza cuando hay una empresa activa, no cuando el usuario tiene
     * empresa propia: el super usuario de plataforma no tiene `empresa_id`
     * pero si trabaja sobre la empresa en la que entro.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && TenantContext::tieneEmpresa();
    }

    public function rules(): array
    {
        return [
            'nombre'         => ['required', 'string', 'max:150'],
            'cuit'           => ['nullable', 'string', 'max:20'],
            'telefono'       => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:150'],
            'direccion'      => ['nullable', 'string', 'max:255'],
            'logo'           => ['nullable', 'image', 'max:2048'],
            'eliminar_logo'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'logo.image'      => 'El logo debe ser una imagen valida.',
            'logo.max'        => 'El logo no puede superar los 2MB.',
            'email.email'     => 'El email no tiene un formato valido.',
        ];
    }
}
