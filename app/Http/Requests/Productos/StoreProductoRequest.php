<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            // --- Datos del Catálogo (Tabla 'productos') ---
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            
            // (NUEVO) Validación para el Proveedor Habitual (CU-25)
            // Es 'nullable' (no requerido) y debe existir en la tabla 'proveedores'
            'proveedor_habitual_id' => 'nullable|exists:proveedores,id',

            // (ELIMINADO) 'stockActual' y 'stockMinimo' ya no se validan aquí.
            
            // --- Datos de Precios (Tabla 'precios_producto') ---
            // Validamos que el frontend envíe los precios como un array
            'precios' => 'required|array|min:1',
            // Validamos cada item dentro del array de precios
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID',
            'precios.*.precio' => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código/SKU es obligatorio',
            'codigo.unique' => 'El código/SKU ya existe en el sistema',
            'nombre.required' => 'El nombre del producto es obligatorio',
            'unidadMedida.required' => 'La unidad de medida es obligatoria',
            'categoriaProductoID.required' => 'Debe seleccionar una categoría',
            'estadoProductoID.required' => 'Debe seleccionar un estado',
            'proveedor_habitual_id.exists' => 'El proveedor seleccionado no es válido',

            'precios.required' => 'Debe ingresar al menos un precio',
            'precios.min' => 'Debe ingresar al menos un precio (minorista o mayorista)',
            'precios.*.tipoClienteID.required' => 'El tipo de cliente para el precio es inválido',
            'precios.*.precio.required' => 'El precio es obligatorio',
            'precios.*.precio.numeric' => 'El precio debe ser un número',
        ];
    }
}