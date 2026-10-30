<?php

namespace App\Support;

/**
 * Resolucion centralizada del tenant (empresa) activo.
 *
 * Es la UNICA fuente de verdad para responder "sobre que empresa estoy
 * trabajando". Antes esta pregunta se contestaba repitiendo
 * `auth()->user()->empresa_id` en cada controller, request y service, lo
 * que produjo ~250 queries sin filtrar y dos fugas reales entre empresas.
 * Centralizarlo cumple el principio de unica responsabilidad (Sommerville)
 * y habilita el nivel de plataforma: un super usuario no pertenece a
 * ninguna empresa, por lo que su tenant activo vive en la sesion.
 *
 * Reglas de resolucion, en orden:
 *  1. Empresa forzada explicitamente (jobs, comandos de consola, tests).
 *  2. Super usuario autenticado  -> empresa elegida en sesion ("entrar como").
 *  3. Usuario comun autenticado  -> su propia `empresa_id`.
 *  4. Sin contexto (consola/cola) -> null, es decir SIN restriccion.
 *
 * El caso 4 es deliberado: los procesos de sistema (monitoreo de SLA,
 * purga de auditoria, seeders) deben poder recorrer todas las empresas.
 * Como contrapartida, ninguna ruta web puede quedar sin tenant resuelto;
 * de eso se encarga el middleware `tenant`.
 */
class TenantContext
{
    /**
     * Clave de sesion donde el super usuario guarda la empresa en la que
     * esta trabajando (patron "log in as" de Shopify / Odoo).
     */
    public const SESSION_KEY = 'empresa_activa_id';

    /**
     * Empresa forzada por codigo, con prioridad sobre la sesion y el usuario.
     * Solo se tiene en cuenta cuando $forzado es true, para poder distinguir
     * "no hay override" de "override explicito a sin-restriccion (null)".
     */
    private static ?int $empresaForzada = null;

    private static bool $forzado = false;

    /**
     * Empresa (tenant) activa, o null si no hay restriccion de tenant.
     */
    public static function empresaId(): ?int
    {
        if (self::$forzado) {
            return self::$empresaForzada;
        }

        // En consola temprana (ej. package discovery) el guard puede no estar
        // disponible todavia; sin auth no hay tenant que resolver.
        if (! app()->bound('auth')) {
            return null;
        }

        $user = auth()->user();

        if (! $user) {
            return null;
        }

        // El super usuario no tiene empresa propia: trabaja sobre la que eligio.
        // Si no eligio ninguna, no hay tenant (y el middleware `tenant` lo
        // mandara a elegir una antes de dejarlo entrar a un modulo).
        if ($user->es_super_admin) {
            $empresaId = session(self::SESSION_KEY);

            return $empresaId ? (int) $empresaId : null;
        }

        return $user->empresa_id ? (int) $user->empresa_id : null;
    }

    /**
     * Indica si hay un tenant resuelto. Las rutas de negocio lo exigen.
     */
    public static function tieneEmpresa(): bool
    {
        return self::empresaId() !== null;
    }

    /**
     * Fija la empresa activa para el super usuario ("entrar como").
     */
    public static function activar(int $empresaId): void
    {
        session([self::SESSION_KEY => $empresaId]);
    }

    /**
     * Saca al super usuario del contexto de la empresa y lo devuelve al
     * nivel de plataforma.
     */
    public static function salir(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Ejecuta un callback con una empresa forzada y restaura el estado
     * anterior al terminar, incluso si el callback lanza una excepcion.
     *
     * Pensado para jobs, comandos y seeders que deben operar sobre un
     * tenant determinado sin que exista un usuario autenticado.
     */
    public static function paraEmpresa(?int $empresaId, callable $callback): mixed
    {
        $empresaAnterior = self::$empresaForzada;
        $forzadoAnterior = self::$forzado;

        self::$empresaForzada = $empresaId;
        self::$forzado = true;

        try {
            return $callback();
        } finally {
            self::$empresaForzada = $empresaAnterior;
            self::$forzado = $forzadoAnterior;
        }
    }

    /**
     * Fija la empresa activa de forma imperativa.
     *
     * A diferencia de paraEmpresa(), no restaura el estado anterior, por lo
     * que su uso correcto exige llamar a limpiar() al terminar. Existe para
     * los hooks de la cola de trabajos, donde el inicio y el fin del
     * contexto ocurren en callbacks distintos y no se puede envolver en uno.
     * En el resto del codigo se prefiere paraEmpresa().
     */
    public static function establecer(?int $empresaId): void
    {
        self::$empresaForzada = $empresaId;
        self::$forzado = true;
    }

    /**
     * Descarta la empresa fijada y vuelve a la resolucion normal.
     */
    public static function limpiar(): void
    {
        self::$empresaForzada = null;
        self::$forzado = false;
    }

    /**
     * Ejecuta un callback sin restriccion de tenant (ve todas las empresas).
     *
     * Valvula de escape para procesos de plataforma: alta de una empresa
     * nueva, pantallas del super usuario, migraciones de datos. Es
     * deliberadamente explicita para que cada uso quede visible en el codigo.
     */
    public static function sinRestriccion(callable $callback): mixed
    {
        return self::paraEmpresa(null, $callback);
    }
}
