<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productoId = $this->route('producto')->id;

        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($productoId)],
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            'stockActual' => 'nullable|integer|min:0',
            'stockMinimo' => 'nullable|integer|min:0',
            'motivo' => 'required|string|min:5|max:255',
            'precios' => 'required|array|min:1',
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID',
            'precios.*.precio' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código/SKU es obligatorio',
            'codigo.unique' => 'El código/SKU ya está en uso por otro producto',
            'nombre.required' => 'El nombre del producto es obligatorio',
            'unidadMedida.required' => 'La unidad de medida es obligatoria',
            'categoriaProductoID.required' => 'Debe seleccionar una categoría',
            'estadoProductoID.required' => 'Debe seleccionar un estado',
            'motivo.required' => 'Debe ingresar un motivo para la modificación',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres',
            'motivo.max' => 'El motivo no puede exceder 255 caracteres',
            'precios.required' => 'Debe ingresar al menos un precio',
            'precios.min' => 'Debe ingresar al menos un precio (minorista o mayorista)',
            'precios.*.precio.required' => 'El precio es obligatorio',
            'precios.*.precio.min' => 'El precio debe ser mayor o igual a 0',
        ];
    }
}
