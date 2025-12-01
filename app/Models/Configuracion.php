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

    /**
     * Obtiene el historial de cambios de una configuración específica
     * CU-31: Versionado y trazabilidad
     */
    public static function historialClave(string $clave)
    {
        return Auditoria::where('tabla_afectada', 'configuracion')
            ->where(function ($query) use ($clave) {
                $query->whereRaw("JSON_EXTRACT(datos_nuevos, '$.clave') = ?", [$clave])
                      ->orWhereRaw("JSON_EXTRACT(datos_anteriores, '$.clave') = ?", [$clave]);
            })
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($auditoria) {
                return [
                    'fecha' => $auditoria->created_at->format('d/m/Y H:i'),
                    'usuario' => $auditoria->usuario->name ?? 'Sistema',
                    'accion' => $auditoria->accion,
                    'valor_anterior' => $auditoria->datos_anteriores['valor'] ?? null,
                    'valor_nuevo' => $auditoria->datos_nuevos['valor'] ?? null,
                    'motivo' => $auditoria->motivo,
                ];
            });
    }

    /**
     * Obtiene todo el historial de cambios de configuración
     */
    public static function historialCompleto(int $limite = 50)
    {
        return Auditoria::where('tabla_afectada', 'configuracion')
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get();
    }
}
