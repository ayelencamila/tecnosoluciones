<?php

namespace App\Http\Resources;

use App\Support\DatoSensible;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Frontera de salida del proveedor (BCE: Boundary).
 *
 * Mismo criterio que ClienteResource: el CUIT y el contacto identifican a un
 * tercero, así que solo salen completos para quien tiene el permiso. La
 * razón social, la calificación y el plazo de entrega quedan siempre a la
 * vista porque son los datos con los que se trabaja al comprar.
 *
 * @property-read \App\Models\Proveedor $resource
 */
class ProveedorResource extends JsonResource
{
    private const CAMPOS_DOMICILIO_OCULTOS = ['calle', 'altura', 'pisoDepto', 'barrio', 'codigoPostal'];

    public function toArray(Request $request): array
    {
        $datos = parent::toArray($request);

        $completo = $request->user()?->puedeVerDatosContacto('proveedores') ?? false;

        $datos['datos_contacto_parciales'] = ! $completo;

        if ($completo) {
            return $datos;
        }

        $datos['cuit'] = DatoSensible::documento($this->cuit);
        $datos['email'] = DatoSensible::email($this->email);
        $datos['telefono'] = DatoSensible::telefono($this->telefono);
        $datos['whatsapp'] = DatoSensible::telefono($this->whatsapp);

        if (is_array($datos['direccion'] ?? null)) {
            foreach (self::CAMPOS_DOMICILIO_OCULTOS as $campo) {
                if (array_key_exists($campo, $datos['direccion'])) {
                    $datos['direccion'][$campo] = blank($datos['direccion'][$campo])
                        ? $datos['direccion'][$campo]
                        : DatoSensible::OCULTO;
                }
            }
        }

        return $datos;
    }
}
