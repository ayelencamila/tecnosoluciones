<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un límite concreto dentro de un plan.
 *
 * `valor === null` significa SIN LÍMITE. No se usa un número centinela: NULL
 * es el valor correcto para "no aplica" y evita que un plan ilimitado termine
 * teniendo un tope escondido.
 */
class PlanLimite extends Model
{
    protected $table = 'plan_limites';

    public const TIPO_DURO = 'duro';

    public const TIPO_BLANDO = 'blando';

    protected $fillable = ['plan_id', 'clave', 'tipo', 'valor'];

    protected $casts = [
        'valor' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function esIlimitado(): bool
    {
        return $this->valor === null;
    }

    /**
     * Un límite duro BLOQUEA la creación del recurso al alcanzarse.
     * Solo corresponde para límites de capacidad; ver config/planes.php.
     */
    public function esDuro(): bool
    {
        return $this->tipo === self::TIPO_DURO && ! $this->esIlimitado();
    }

    public function excedidoCon(int $consumo): bool
    {
        return ! $this->esIlimitado() && $consumo >= $this->valor;
    }
}
