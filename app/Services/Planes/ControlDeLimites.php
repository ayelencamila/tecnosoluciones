<?php

namespace App\Services\Planes;

use App\Exceptions\LimiteDePlanExcedido;
use App\Models\Empresa;
use App\Models\PlanLimite;
use App\Support\TenantContext;

/**
 * Consulta y aplicación de los límites del plan de una empresa.
 *
 * Reglas de diseño
 * ----------------
 * - Una empresa SIN plan asignado no tiene límites. Así, agregar la
 *   funcionalidad no deja afuera a las empresas que ya existían.
 *
 * - Un plan que no define una clave tampoco limita esa clave. Agregar un
 *   límite nuevo al catálogo no restringe retroactivamente a nadie.
 *
 * - El operador de la plataforma NO queda sujeto a los límites. Si lo
 *   estuviera, el día que un cliente llega al tope no se podría entrar a su
 *   sistema a ayudarlo, que es justo cuando hace falta.
 *
 * - Los conteos corren SIN el scope de empresa (`withoutGlobalScopes` en
 *   config/planes.php) y filtran por empresa_id explícitamente, porque este
 *   servicio también se usa desde el panel de plataforma, donde el tenant
 *   activo puede ser otro o ninguno.
 */
class ControlDeLimites
{
    /** @var array<string, int> cache por request: contar es costoso */
    private array $consumos = [];

    public function __construct(private readonly ?Empresa $empresa = null) {}

    /**
     * Instancia para la empresa que corresponde al contexto actual.
     */
    public static function paraTenantActivo(): self
    {
        $empresaId = TenantContext::empresaId();

        $empresa = $empresaId
            ? Empresa::with('plan.limites')->find($empresaId)
            : null;

        return new self($empresa);
    }

    public static function paraEmpresa(Empresa $empresa): self
    {
        $empresa->loadMissing('plan.limites');

        return new self($empresa);
    }

    /**
     * El límite configurado para una clave, o null si no hay ninguno.
     */
    public function limite(string $clave): ?PlanLimite
    {
        if (! $this->empresa?->plan) {
            return null;
        }

        $limite = $this->empresa->plan->limite($clave);

        // Un límite con valor null es "sin límite": equivale a no tenerlo.
        return $limite?->esIlimitado() ? null : $limite;
    }

    /**
     * Consumo actual de una clave, según la forma de contar declarada en
     * config/planes.php.
     */
    public function consumo(string $clave): int
    {
        if (! $this->empresa) {
            return 0;
        }

        if (array_key_exists($clave, $this->consumos)) {
            return $this->consumos[$clave];
        }

        $contador = config("planes.limites.{$clave}.contar");

        if (! is_callable($contador)) {
            return $this->consumos[$clave] = 0;
        }

        return $this->consumos[$clave] = (int) $contador($this->empresa->id);
    }

    /**
     * Si ya se alcanzó el límite. No distingue duro de blando: sirve tanto
     * para bloquear como para avisar.
     */
    public function alcanzado(string $clave): bool
    {
        $limite = $this->limite($clave);

        return $limite !== null && $limite->excedidoCon($this->consumo($clave));
    }

    /**
     * Si se está cerca del límite (por defecto, al 80%). Avisar recién al
     * 100% no sirve: para entonces ya no hay margen para decidir.
     */
    public function cerca(string $clave): bool
    {
        $limite = $this->limite($clave);

        if ($limite === null || $this->alcanzado($clave)) {
            return false;
        }

        $umbral = (float) config('planes.umbral_aviso', 0.8);

        return $this->consumo($clave) >= (int) ceil($limite->valor * $umbral);
    }

    /**
     * Exige que haya cupo antes de crear un recurso.
     *
     * Solo corta si el límite es DURO. Un límite blando nunca interrumpe la
     * operación: se cuenta y se avisa, pero la venta se registra igual.
     *
     * @throws LimiteDePlanExcedido
     */
    public function exigirCupo(string $clave): void
    {
        $limite = $this->limite($clave);

        if ($limite === null || ! $limite->esDuro()) {
            return;
        }

        $consumo = $this->consumo($clave);

        if (! $limite->excedidoCon($consumo)) {
            return;
        }

        $etiqueta = config("planes.limites.{$clave}.label", $clave);
        $plan = $this->empresa?->plan?->nombre ?? 'actual';

        throw new LimiteDePlanExcedido(
            $clave,
            $limite->valor,
            $consumo,
            "El plan {$plan} permite hasta {$limite->valor} ".mb_strtolower($etiqueta)
            .". Para agregar más, hay que pasar a un plan superior."
        );
    }

    /**
     * Resumen de todos los límites, para mostrarlo en pantalla.
     *
     * @return list<array<string, mixed>>
     */
    public function resumen(): array
    {
        if (! $this->empresa?->plan) {
            return [];
        }

        $resumen = [];

        foreach ($this->empresa->plan->limites as $limite) {
            $consumo = $this->consumo($limite->clave);
            $definicion = config("planes.limites.{$limite->clave}", []);

            $resumen[] = [
                'clave' => $limite->clave,
                'label' => $definicion['label'] ?? $limite->clave,
                'periodo' => $definicion['periodo'] ?? null,
                'tipo' => $limite->tipo,
                'valor' => $limite->valor,
                'ilimitado' => $limite->esIlimitado(),
                'consumo' => $consumo,
                'alcanzado' => $limite->excedidoCon($consumo),
                'cerca' => $this->cerca($limite->clave),
            ];
        }

        return $resumen;
    }
}
