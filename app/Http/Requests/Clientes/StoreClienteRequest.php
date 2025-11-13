<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class para validación de creación de clientes
 * Cumple con Larman (separación de validación) y Kendall (mensajes claros).
 */
class StoreClienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        // Dejamos que el Controlador o una futura Policy se encargue.
        return true; 
    }

    /**
     * Obtiene las reglas de validación que aplican a la solicitud.
     */
    public function rules(): array
    {
        // Reglas del archivo que creamos (más refinadas)
        return [
            // Datos personales
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'DNI' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'DNI'), // Valida unicidad en la tabla clientes
            ],
            'mail' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20', // Lo dejamos nullable, es más flexible
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
            
            // Reglas de Cuenta Corriente (solo si es Mayorista)
            'limiteCredito' => 'nullable|numeric|min:0',
            'diasGracia' => 'nullable|integer|min:0',
            'estado_cuenta_corriente_id' => 'nullable|exists:estados_cuenta_corriente,estadoCuentaCorrienteID',
        ];
    }

    /**
     * MEJORA KENDALL: Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'DNI.required' => 'El DNI es obligatorio.',
            'DNI.unique' => 'Ya existe un cliente con este DNI.',
            'mail.email' => 'El formato del correo electrónico no es válido.',
            'calle.required' => 'La calle es obligatoria.',
            'altura.required' => 'La altura es obligatoria.',
            'codigoPostal.required' => 'El código postal es obligatorio.',
            'provincia_id.required' => 'Debe seleccionar una provincia.',
            'localidad_id.required' => 'Debe seleccionar una localidad.',
            'tipo_cliente_id.required' => 'Debe seleccionar un tipo de cliente.',
            'estado_cliente_id.required' => 'Debe seleccionar un estado de cliente.',
        ];
    }

    /**
     * MEJORA KENDALL: Obtiene los nombres de atributos personalizados.
     */
    public function attributes(): array
    {
        return [
            'DNI' => 'DNI',
            'mail' => 'correo electrónico',
            'whatsapp' => 'WhatsApp',
            'telefono' => 'teléfono',
            'codigoPostal' => 'código postal',
            'provincia_id' => 'provincia',
            'localidad_id' => 'localidad',
            'tipo_cliente_id' => 'tipo de cliente',
            'estado_cliente_id' => 'estado del cliente',
            'limiteCredito' => 'límite de crédito',
            'diasGracia' => 'días de gracia',
        ];
    }
}