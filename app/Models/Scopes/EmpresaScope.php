<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope de aislamiento multi-tenant.
 *
 * Agrega `WHERE <tabla>.empresa_id = <empresa activa>` a TODA consulta
 * Eloquent del modelo que lo use: listados, `find()`, route-model binding,
 * relaciones, agregados y subconsultas.
 *
 * Por que un Global Scope y no filtros manuales: el filtrado manual es
 * opt-in, de modo que olvidarse en un solo lugar produce una fuga entre
 * empresas. El scope invierte el default a "aislado salvo que se pida lo
 * contrario", que es la unica forma de garantizar aislamiento en un sistema
 * con cientos de consultas (seguridad por defecto, Sommerville).
 *
 * El nombre de la columna se califica con la tabla a proposito: muchos
 * listados hacen JOIN con tablas que tambien tienen `empresa_id`, y un
 * `where('empresa_id', ...)` sin calificar seria ambiguo para MySQL.
 */
class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $empresaId = TenantContext::empresaId();

        // Sin tenant resuelto (consola, cola, seeders) no se restringe: son
        // procesos de sistema que deben recorrer todas las empresas. Las
        // rutas web nunca llegan aca sin tenant gracias al middleware `tenant`.
        if ($empresaId === null) {
            return;
        }

        $builder->where($model->getTable().'.empresa_id', $empresaId);
    }
}
