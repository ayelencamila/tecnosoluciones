<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuracion';

    protected $primaryKey = 'configuracionID';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * Obtiene un valor de configuración por su clave.
     */
    public static function get(string $clave, mixed $default = null): mixed
    {
        $config = self::where('clave', $clave)->first();

        return $config ? $config->valor : $default;
    }

    /**
     * Establece o actualiza un valor de configuración por su clave.
     */
    public static function set(string $clave, mixed $valor, ?string $descripcion = null): bool
    {
        $config = self::firstOrNew(['clave' => $clave]);
        $config->valor = (string) $valor; // Guardar siempre como string
        if ($descripcion) {
            $config->descripcion = $descripcion;
        }
        $guardado = $config->save();

        // Opcional: Registrar auditoría si el valor cambió
        if ($guardado && $config->isDirty('valor')) {
            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_PARAMETRO_GLOBAL,
                'configuracion',
                $config->configuracionID,
                $config->getOriginal(),
                $config->toArray(),
                "Parámetro global modificado: {$clave}",
                "Valor anterior: {$config->getOriginal('valor')}, Valor nuevo: {$config->valor}",
                auth()->id() // Asume que hay un usuario logueado
            );
        }

        return $guardado;
    }

    /**
     * Casts para tipos de datos comunes si es necesario.
     * Ejemplo: para obtener un integer
     */
    public static function getInt(string $clave, int $default = 0): int
    {
        return (int) self::get($clave, $default);
    }

    public static function getBool(string $clave, bool $default = false): bool
    {
        return filter_var(self::get($clave, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
    }
}
