<?php

// 1. CORRECCIÓN: El namespace ahora apunta a la subcarpeta 'Clientes'
namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Cliente; // Importamos Cliente para el type-hinting

/**
 * Request class para validación de actualización de clientes
 * (Ubicación y Sincronización Corregidas)
 */
class UpdateClienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta request
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Obtiene las reglas de validación que se aplican a la request
     */
    public function rules(): array
    {
        // Obtenemos el cliente de la ruta de forma segura
        $clienteId = $this->route('cliente')->clienteID;

        return [
            // Datos personales
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'DNI' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'DNI')->ignore($clienteId, 'clienteID'), // Perfecto
            ],
            'mail' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20', // <-- MEJORA: Sincronizado con Store (nullable)
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

            // Cuenta corriente
            'limiteCredito' => 'nullable|numeric|min:0',
            'diasGracia' => 'nullable|integer|min:0',
            'estado_cuenta_corriente_id' => 'nullable|exists:estados_cuenta_corriente,estadoCuentaCorrienteID',
        ];
    }

    /**
     * MEJORA KENDALL: Mensajes de error personalizados
     */
    public function messages(): array
    {
        // (Tu código de 'messages' era perfecto, lo mantenemos)
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'DNI.required' => 'El DNI es obligatorio.',
            'DNI.unique' => 'Ya existe otro cliente con este DNI.',
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
     * MEJORA KENDALL: Nombres de atributos personalizados
     */
    public function attributes(): array
    {
         // (Tu código de 'attributes' era perfecto, lo mantenemos)
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

    /**
     * MEJORA KENDALL: Limpieza de datos antes de validar
     */
    protected function prepareForValidation(): void
    {
        // (Tu código 'prepareForValidation' era perfecto, lo mantenemos)
        if ($this->has('DNI')) {
            $this->merge([
                'DNI' => trim($this->DNI),
            ]);
        }
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre),
            ]);
        }
        if ($this->has('apellido')) {
            $this->merge([
                'apellido' => trim($this->apellido),
            ]);
        }
    }
}