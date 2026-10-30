<?php

namespace App\Console\Concerns;

use App\Models\Empresa;
use App\Support\TenantContext;

/**
 * Permite que un comando programado se ejecute una vez por cada empresa
 * activa, con el tenant correspondiente establecido.
 *
 * Los comandos del scheduler corren sin usuario autenticado, de modo que
 * TenantContext no resuelve ninguna empresa y el Global Scope no restringe.
 * Para una tarea de mantenimiento de plataforma (purgar auditoria) eso es
 * lo correcto, pero para una tarea de negocio es un error: el monitoreo de
 * stock leeria `compras_dias_vencimiento` de una empresa cualquiera y lo
 * aplicaria a los productos de todas, y las alertas de SLA se generarian
 * mezclando reparaciones de distintos clientes.
 *
 * Al ejecutar el cuerpo del comando dentro de paraEmpresa(), todo lo que
 * ocurre adentro queda correctamente aislado: las consultas por el Global
 * Scope, los parametros de `configuracion`, y los jobs despachados, que
 * heredan la empresa activa en su payload.
 *
 * Las empresas suspendidas se omiten: si su acceso esta cortado, tampoco
 * corresponde seguir generando notificaciones en su nombre.
 */
trait EjecutaPorEmpresa
{
    protected function porCadaEmpresa(callable $callback): int
    {
        $empresas = Empresa::where('activa', true)->orderBy('id')->get();

        if ($empresas->isEmpty()) {
            $this->warn('No hay empresas activas para procesar.');

            return self::SUCCESS;
        }

        $codigoSalida = self::SUCCESS;

        foreach ($empresas as $empresa) {
            $this->newLine();
            $this->line("──── Empresa #{$empresa->id}: {$empresa->nombre}");

            $resultado = TenantContext::paraEmpresa(
                $empresa->id,
                fn () => $callback($empresa)
            );

            // Un fallo en una empresa no detiene a las demas, pero si se
            // refleja en el codigo de salida para que el scheduler lo registre.
            if (is_int($resultado) && $resultado !== self::SUCCESS) {
                $codigoSalida = $resultado;
            }
        }

        return $codigoSalida;
    }
}
