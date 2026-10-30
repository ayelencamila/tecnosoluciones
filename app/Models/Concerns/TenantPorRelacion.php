<?php

namespace App\Models\Concerns;

use App\Models\Scopes\EmpresaPorRelacionScope;

/**
 * Hace tenant-aware a un modelo hijo, que obtiene la empresa de su padre.
 *
 * El modelo declara de que relacion cuelga:
 *
 *     class Stock extends Model
 *     {
 *         use TenantPorRelacion;
 *
 *         protected static string $relacionTenant = 'producto';
 *     }
 *
 * La relacion elegida debe apuntar a un modelo que use BelongsToEmpresa y su
 * clave foranea no puede ser nullable: el scope exige que el padre exista
 * (EXISTS), de modo que una fila sin padre quedaria invisible. Cuando el
 * padre es opcional, corresponde darle a la tabla su propia `empresa_id` y
 * usar BelongsToEmpresa en su lugar, como se hizo con
 * `recepciones_mercaderia`.
 */
trait TenantPorRelacion
{
    public static function bootTenantPorRelacion(): void
    {
        static::addGlobalScope(new EmpresaPorRelacionScope);
    }

    /**
     * Nombre de la relacion por la que se resuelve el tenant.
     */
    public function relacionTenant(): string
    {
        return static::$relacionTenant;
    }
}
