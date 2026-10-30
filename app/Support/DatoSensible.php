<?php

namespace App\Support;

/**
 * Enmascarado de datos personales en las salidas del sistema.
 *
 * CLAUDE.md (Auditoría Legal, punto 4) exige que correos, teléfonos y
 * direcciones no queden expuestos a usuarios sin el rol adecuado. La opción
 * de borrarlos del todo haría inservible el listado: quien lo mira necesita
 * reconocer la fila que tiene delante.
 *
 * Por eso, siguiendo los lineamientos de salida de Kendall & Kendall, se
 * conserva la parte del dato que sirve para IDENTIFICAR el registro y se
 * oculta la que sirve para CONTACTAR a la persona. Es la misma convención
 * que el usuario ya conoce de su resumen bancario, así que no necesita
 * explicación adicional.
 *
 * El dato completo nunca sale del servidor: el enmascarado ocurre antes de
 * serializar la respuesta, no en el navegador.
 */
class DatoSensible
{
    /** Carácter de relleno. No se usa '*' para no confundirlo con un dato real. */
    private const RELLENO = '•';

    /** Reemplazo de un texto que se oculta por completo. */
    public const OCULTO = '••••••';

    /**
     * Conserva la inicial y el dominio: "juan.perez@gmail.com" -> "j•••@gmail.com".
     *
     * El dominio queda a la vista porque permite distinguir un correo
     * personal de uno laboral sin revelar la casilla.
     */
    public static function email(?string $email): ?string
    {
        if (blank($email)) {
            return $email;
        }

        $partes = explode('@', $email, 2);

        if (count($partes) < 2 || blank($partes[1])) {
            return self::OCULTO;
        }

        return mb_substr($partes[0], 0, 1).str_repeat(self::RELLENO, 3).'@'.$partes[1];
    }

    /**
     * Conserva los últimos 4 dígitos: "+5493764123456" -> "••••3456".
     *
     * Cuatro dígitos alcanzan para cotejar una llamada entrante contra la
     * ficha, que es la tarea real del operador, y no para marcar el número.
     */
    public static function telefono(?string $telefono): ?string
    {
        return self::ultimosDigitos($telefono, 4);
    }

    /**
     * Conserva los últimos 3 dígitos: "30123456" -> "•••456".
     *
     * Se expone menos que en el teléfono porque el documento no se usa para
     * contactar sino para desambiguar homónimos, y para eso bastan 3.
     */
    public static function documento(?string $documento): ?string
    {
        return self::ultimosDigitos($documento, 3);
    }

    /**
     * Devuelve los últimos $visibles caracteres precedidos de relleno.
     *
     * El relleno es de largo FIJO (4) y no proporcional al dato original, a
     * propósito: un relleno proporcional revelaría cuántos dígitos tiene el
     * valor real, que es información que no hace falta entregar.
     *
     * Si el dato es más corto que lo que habría que mostrar, se oculta
     * entero: mostrar "•4" no protege nada.
     */
    private static function ultimosDigitos(?string $valor, int $visibles): ?string
    {
        if (blank($valor)) {
            return $valor;
        }

        $limpio = preg_replace('/\D/', '', $valor) ?? '';

        if (mb_strlen($limpio) <= $visibles) {
            return self::OCULTO;
        }

        return str_repeat(self::RELLENO, 4).mb_substr($limpio, -$visibles);
    }
}
