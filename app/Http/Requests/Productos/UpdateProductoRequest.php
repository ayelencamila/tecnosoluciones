<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asumimos que la ruta ya está protegida
    }

    public function rules(): array
    {
        // Obtenemos el ID del producto que se está actualizando
        $productoId = $this->route('producto')->id;

        return [
            // --- Datos del Catálogo (Tabla 'productos') ---
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($productoId)],
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            'stock_minimo' => 'nullable|integer|min:0',
            
            // (NUEVO) Validación para el Proveedor Habitual (CU-26)
            'proveedor_habitual_id' => 'nullable|exists:proveedores,id',

            // (ELIMINADO) 'stockActual' y 'stockMinimo' ya no se validan aquí.
            
            // --- Motivo de Auditoría (CU-26, Paso 6) ---
            'motivo' => 'required|string|min:5|max:255', 
            
            // --- Datos de Precios (Tabla 'precios_producto') ---
            'precios' => 'required|array|min:1',
            // Verificamos la PK correcta de tipos_cliente ('tipoClienteID')
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID', 
            'precios.*.precio' => 'required|numeric|min:0|max:99999999.99',
            
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código/SKU ya está en uso por otro producto',
            'nombre.required' => 'El nombre del producto es obligatorio',
            'proveedor_habitual_id.exists' => 'El proveedor seleccionado no es válido',
            
            'motivo.required' => 'Debe ingresar un motivo para la modificación (requerido por auditoría)',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres',
            
            'precios.required' => 'Debe ingresar al menos un precio',
            'precios.*.precio.numeric' => 'El precio debe ser un número válido',
            'precios.*.tipoClienteID.required' => 'El tipo de cliente para el precio es inválido',
        ];
    }
}