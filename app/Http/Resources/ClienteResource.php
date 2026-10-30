<?php

namespace App\Http\Resources;

use App\Support\DatoSensible;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Frontera de salida del cliente (BCE: Boundary).
 *
 * La entidad conserva todos sus atributos; es acá, al momento de serializar,
 * donde se decide qué ve cada usuario según sus permisos. De ese modo ningún
 * controlador ni servicio necesita acordarse de filtrar, y el dato completo
 * no sale del servidor salvo que corresponda.
 *
 * Se parte de la salida completa y se SOBREESCRIBEN los campos sensibles, en
 * lugar de rearmar el arreglo campo por campo. Es deliberado: así agregar una
 * columna al cliente no exige tocar este archivo, y sobre todo no se puede
 * romper una vista por olvidar una clave.
 *
 * @property-read \App\Models\Cliente $resource
 */
class ClienteResource extends JsonResource
{
    /**
     * Campos del domicilio que se ocultan. La localidad y la provincia
     * quedan visibles porque el listado filtra por ellas y los reportes las
     * agrupan, y porque indican una zona, no un domicilio.
     */
    private const CAMPOS_DOMICILIO_OCULTOS = ['calle', 'altura', 'pisoDepto', 'barrio', 'codigoPostal'];

    public function toArray(Request $request): array
    {
        $datos = parent::toArray($request);

        $completo = $request->user()?->puedeVerDatosContacto('clientes') ?? false;

        // Bandera para la interfaz: Kendall & Kendall piden que una salida
        // recortada se muestre como tal, para que el usuario no crea que el
        // sistema tiene el dato incompleto y lo "corrija".
        $datos['datos_contacto_parciales'] = ! $completo;

        if ($completo) {
            return $datos;
        }

        $datos['DNI'] = DatoSensible::documento($this->DNI);
        $datos['mail'] = DatoSensible::email($this->mail);
        $datos['whatsapp'] = DatoSensible::telefono($this->whatsapp);
        $datos['telefono'] = DatoSensible::telefono($this->telefono);

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
