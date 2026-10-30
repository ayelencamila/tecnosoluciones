<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'configuracion';

    protected $primaryKey = 'configuracionID';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * Clave de caché del parámetro, particionada por empresa.
     *
     * Imprescindible desde que `configuracion` es por empresa: una clave de
     * caché compartida (`config:stock_minimo_global`) haría que el valor de
     * la primera empresa que consulte se le sirva a todas las demás. Es una
     * fuga entre tenants que el Global Scope no puede evitar, porque ocurre
     * antes de llegar a la base de datos.
     */
    private static function cacheKey(string $clave): string
    {
        return 'config:'.(TenantContext::empresaId() ?? 'sin-empresa').":{$clave}";
    }

    /**
     * Obtiene un valor de configuración por su clave con caché.
     * TTL: 1 hora (3600 segundos)
     */
    public static function get(string $clave, mixed $default = null): mixed
    {
        return cache()->remember(self::cacheKey($clave), 3600, function () use ($clave, $default) {
            $config = self::where('clave', $clave)->first();
            return $config ? $config->valor : $default;
        });
    }

    /**
     * Establece o actualiza un valor de configuración por su clave.
     * Invalida el caché automáticamente.
     */
    public static function set(string $clave, mixed $valor, ?string $descripcion = null): bool
    {
        $config = self::firstOrNew(['clave' => $clave]);
        $config->valor = (string) $valor; // Guardar siempre como string
        if ($descripcion) {
            $config->descripcion = $descripcion;
        }

        // El estado "sucio" se captura ANTES de guardar: despues de save() el
        // modelo ya esta sincronizado y isDirty() siempre devuelve false, por
        // lo que la auditoria de cambios de parametros nunca se registraba.
        $valorCambio = $config->isDirty('valor');
        $valorAnterior = $config->getOriginal('valor');

        $guardado = $config->save();

        // Invalidar caché para esta clave
        cache()->forget(self::cacheKey($clave));

        // Registrar auditoría si el valor cambió
        if ($guardado && $valorCambio) {
            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_PARAMETRO_GLOBAL,
                'configuracion',
                $config->configuracionID,
                $config->getOriginal(),
                $config->toArray(),
                "Parámetro global modificado: {$clave}",
                "Valor anterior: {$valorAnterior}, Valor nuevo: {$config->valor}",
                auth()->id() // Asume que hay un usuario logueado
            );
        }

        return $guardado;
    }

    /**
     * Alias para mantener compatibilidad con código legacy
     */
    public static function valor(string $clave, mixed $default = null): mixed
    {
        return self::get($clave, $default);
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
     * Limpia toda la caché de configuración
     * Útil cuando se realizan múltiples cambios o importaciones
     */
    public static function clearCache(): void
    {
        $configs = self::all();
        foreach ($configs as $config) {
            cache()->forget(self::cacheKey($config->clave));
        }
    }

    /**
     * Pre-carga todas las configuraciones en caché
     * Útil para optimizar cuando se necesitan múltiples valores
     */
    public static function warmCache(): void
    {
        $configs = self::all();
        foreach ($configs as $config) {
            cache()->put(self::cacheKey($config->clave), $config->valor, 3600);
        }
    }
}
