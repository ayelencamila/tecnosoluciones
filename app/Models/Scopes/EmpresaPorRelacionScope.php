<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aislamiento multi-tenant para tablas hijas, que no tienen `empresa_id`.
 *
 * Tablas como `stock`, `detalle_ventas` o `alertas_reparacion` heredan el
 * tenant de su padre por integridad referencial, pero eso NO alcanza para
 * las consultas: una query que ARRANCA en la tabla hija no pasa por el padre
 * y queda sin filtrar. Es el caso que hacia que el dashboard contara el
 * stock critico de todas las empresas juntas.
 *
 * La solucion no duplica `empresa_id` en cada tabla hija: se exige que exista
 * el padre, y como el padre lleva su propio Global Scope de empresa, la
 * condicion de tenant viaja dentro del EXISTS. Asi el aislamiento se define
 * una sola vez, en la entidad que realmente posee el atributo.
 *
 * Se preserva `withTrashed()` sobre el padre a proposito: antes de este
 * cambio, una fila hija cuyo padre estuviera dado de baja logicamente seguia
 * contando en los listados y los KPI. Sin esta precaucion, el scope ademas de
 * aislar cambiaria esos numeros, que es un efecto que no corresponde.
 */
class EmpresaPorRelacionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::empresaId() === null) {
            return;
        }

        $builder->whereHas($model->relacionTenant(), function (Builder $padre) {
            if (in_array(SoftDeletes::class, class_uses_recursive($padre->getModel()), true)) {
                $padre->withTrashed();
            }
        });
    }
}
