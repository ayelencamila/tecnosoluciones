<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convierte un modelo en tenant-aware. Cubre las dos mitades del problema:
 *
 *  - LECTURA: aplica EmpresaScope, de modo que ninguna consulta pueda ver
 *    datos de otra empresa (ver justificacion en la clase del scope).
 *  - ESCRITURA: completa `empresa_id` al crear, para que sea imposible
 *    guardar una fila sin tenant o atribuirla a la empresa equivocada por
 *    olvido en un controller.
 *
 * Si el llamador ya seteo `empresa_id` explicitamente, se respeta: eso
 * permite que el alta de una empresa nueva (nivel plataforma) cree las
 * filas iniciales del tenant que todavia no es el tenant activo.
 */
trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::creating(function (Model $model) {
            if (empty($model->empresa_id)) {
                $empresaId = TenantContext::empresaId();

                if ($empresaId !== null) {
                    $model->empresa_id = $empresaId;
                }
            }
        });
    }

    /**
     * Empresa (tenant) propietaria de la fila.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');
    }
}
