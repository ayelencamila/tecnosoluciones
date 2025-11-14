<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class para validación de creación de clientes
 * 
 * Esta clase encapsula todas las reglas de validación necesarias
 * para el registro de un nuevo cliente en el sistema.
 */
class ClienteStoreRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta request
     */
    public function authorize(): bool
    {
        return true; // Temporalmente true hasta implementar auth completo
    }

    /**
     * Obtiene las reglas de validación que se aplican a la request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Datos personales
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'DNI' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'DNI'),
            ],
            'mail' => 'nullable|email|max:255',
            'whatsapp' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            
            // Dirección
            'calle' => 'required|string|max:255',
            'altura' => 'required|string|max:50',
            'pisoDepto' => 'nullable|string|max:50',
            'barrio' => 'nullable|string|max:255',
            'codigoPostal' => 'required|string|max:10',
            'provincia_id' => 'required|exists:provincias,provinciaID',
            'localidad_id' => 'required|exists:localidades,localidadID',
            
            // Clasificación
            'tipo_cliente_id' => 'required|exists:tipos_cliente,tipoClienteID',
            'estado_cliente_id' => 'required|exists:estados_cliente,estadoClienteID',
            
            // Cuenta corriente (condicional para Mayoristas)
            'limiteCredito' => 'nullable|numeric|min:0',
            'diasGracia' => 'nullable|integer|min:0',
            'estado_cuenta_corriente_id' => 'nullable|exists:estados_cuenta_corriente,estadoCuentaCorrienteID',
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'DNI.required' => 'El DNI es obligatorio.',
            'DNI.unique' => 'Ya existe un cliente con este DNI.',
            'mail.email' => 'El formato del correo electrónico no es válido.',
            'whatsapp.required' => 'El número de WhatsApp es obligatorio.',
            'calle.required' => 'La calle es obligatoria.',
            'altura.required' => 'La altura es obligatoria.',
            'codigoPostal.required' => 'El código postal es obligatorio.',
            'provincia_id.required' => 'Debe seleccionar una provincia.',
            'provincia_id.exists' => 'La provincia seleccionada no es válida.',
            'localidad_id.required' => 'Debe seleccionar una localidad.',
            'localidad_id.exists' => 'La localidad seleccionada no es válida.',
            'tipo_cliente_id.required' => 'Debe seleccionar un tipo de cliente.',
            'tipo_cliente_id.exists' => 'El tipo de cliente seleccionado no es válido.',
            'estado_cliente_id.required' => 'Debe seleccionar un estado de cliente.',
            'estado_cliente_id.exists' => 'El estado de cliente seleccionado no es válido.',
            'limiteCredito.numeric' => 'El límite de crédito debe ser un número.',
            'limiteCredito.min' => 'El límite de crédito no puede ser negativo.',
            'diasGracia.integer' => 'Los días de gracia deben ser un número entero.',
            'diasGracia.min' => 'Los días de gracia no pueden ser negativos.',
            'estado_cuenta_corriente_id.exists' => 'El estado de cuenta corriente seleccionado no es válido.',
        ];
    }

    /**
     * Obtiene los nombres de atributos personalizados para los mensajes de error
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'apellido' => 'apellido',
            'DNI' => 'DNI',
            'mail' => 'correo electrónico',
            'whatsapp' => 'WhatsApp',
            'telefono' => 'teléfono',
            'calle' => 'calle',
            'altura' => 'altura',
            'pisoDepto' => 'piso/departamento',
            'barrio' => 'barrio',
            'codigoPostal' => 'código postal',
            'provincia_id' => 'provincia',
            'localidad_id' => 'localidad',
            'tipo_cliente_id' => 'tipo de cliente',
            'estado_cliente_id' => 'estado del cliente',
            'limiteCredito' => 'límite de crédito',
            'diasGracia' => 'días de gracia',
            'estado_cuenta_corriente_id' => 'estado de cuenta corriente',
        ];
    }
}
