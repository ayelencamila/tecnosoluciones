<?php

namespace App\Console\Commands;

use App\Console\Concerns\EjecutaPorEmpresa;
use App\Models\MovimientoStock;
use App\Models\Stock;
use App\Models\TipoMovimientoStock;
use Illuminate\Console\Command;

/**
 * Comprueba que el libro de movimientos y la tabla de stock digan lo mismo.
 *
 * Por que hace falta
 * ------------------
 * `movimientos_stock` es el libro contable del deposito: cada fila dice de
 * cuanto a cuanto paso una existencia y por que. `stock.cantidad_disponible`
 * es el saldo. Si el libro y el saldo no coinciden, uno de los dos miente, y
 * el comercio no puede saber cual.
 *
 * Eso puede pasar por dos motivos:
 *
 *  1. Algun camino de codigo modifica el stock sin registrar el movimiento.
 *  2. Alguien toco la base directamente (tipico en desarrollo).
 *
 * El comando no distingue entre los dos: solo senala donde no cuadra, que es
 * justo lo que no se puede ver a simple vista.
 *
 * Se revisan DOS cosas distintas:
 *
 *  - CONTINUIDAD: que el `stockAnterior` de cada movimiento sea igual al
 *    `stockNuevo` del anterior. Un corte aca significa que entre los dos
 *    movimientos el saldo cambio sin dejar rastro.
 *
 *  - SALDO FINAL: que el `stockNuevo` del ultimo movimiento sea igual a la
 *    cantidad disponible de hoy.
 */
class VerificarIntegridadStock extends Command
{
    use EjecutaPorEmpresa;

    protected $signature = 'stock:verificar-integridad
        {--empresa= : Revisar solo una empresa}
        {--corregir : Registrar un movimiento de correccion para cuadrar el saldo}';

    protected $description = 'Compara el historial de movimientos con el saldo de stock y reporta diferencias';

    /**
     * Con --corregir NO se toca el saldo ni se reescribe el pasado: se agrega
     * un movimiento de ajuste que explica la diferencia, que es lo que haria
     * un contador ante un faltante de inventario. El saldo real es el que
     * manda --es lo que hay en el deposito-- y el libro se pone al dia
     * dejando constancia de que hubo una correccion.
     *
     * No corrige los cortes INTERMEDIOS de la cadena: esos ya ocurrieron y
     * borrarlos seria falsear la historia. Solo cuadra el saldo final.
     */

    public function handle(): int
    {
        if ($empresaId = $this->option('empresa')) {
            return \App\Support\TenantContext::paraEmpresa(
                (int) $empresaId,
                fn () => $this->revisarEmpresa()
            );
        }

        return $this->porCadaEmpresa(fn () => $this->revisarEmpresa());
    }

    private function revisarEmpresa(): int
    {
        // Se separan dos severidades distintas, porque exigen reacciones
        // distintas:
        //
        //  - SALDO que no cuadra: es ACCIONABLE hoy. El numero que ve el
        //    comercio no coincide con su historial y hay que resolverlo.
        //
        //  - CORTE historico: ya ocurrio. Queda como registro de que ese dia
        //    el saldo cambio sin anotarse, pero no hay nada que hacer al
        //    respecto y no deberia volver a alertar cada semana. Si el chequeo
        //    automatico avisara por esto, se convertiria en ruido y se
        //    dejaria de mirar, que es la peor forma de perder una alarma.
        $saldos = [];
        $cortes = [];

        $stocks = Stock::with('producto')->get();

        foreach ($stocks as $stock) {
            $movimientos = MovimientoStock::where('stock_id', $stock->stock_id)
                ->orderBy('fecha_movimiento')
                ->orderBy('id')
                ->get();

            $producto = $stock->producto?->nombre ?? "producto #{$stock->productoID}";

            if ($movimientos->isEmpty()) {
                // Sin movimientos, el saldo solo puede ser el inicial. Si hay
                // existencias sin un solo movimiento que las explique, tampoco
                // cuadra: entraron sin registrarse.
                if ($stock->cantidad_disponible > 0) {
                    $saldos[] = [$producto, 'sin movimientos', '-', $stock->cantidad_disponible,
                        'Hay existencias que ningun movimiento explica'];
                }

                continue;
            }

            // 1. Continuidad de la cadena
            $esperado = null;

            foreach ($movimientos as $mov) {
                if ($esperado !== null && $mov->stockAnterior !== $esperado) {
                    $cortes[] = [$producto, "movimiento #{$mov->id}",
                        $mov->fecha_movimiento?->format('d/m/Y') ?? '-',
                        $esperado, $mov->stockAnterior];
                }

                $esperado = $mov->stockNuevo;
            }

            // 2. Saldo final
            if ($stock->cantidad_disponible !== $esperado) {
                $saldos[] = [$producto, 'saldo actual', $esperado, $stock->cantidad_disponible,
                    'El saldo no coincide con el ultimo movimiento'];
            }
        }

        // Informativo: cortes que ya ocurrieron. No hacen fallar el comando.
        if (! empty($cortes)) {
            $this->line('ℹ️  '.count($cortes).' corte(s) historico(s) en la cadena (el saldo cambio sin registrarse ese dia):');
            $this->table(['Producto', 'Donde', 'Fecha', 'Venia de', 'Arranco en'], $cortes);
            $this->line('   Quedan como registro. No se corrigen: borrarlos seria falsear la historia.');
            $this->newLine();
        }

        if (empty($saldos)) {
            $this->info('✅ Todos los saldos coinciden con su historial.');

            return self::SUCCESS;
        }

        $this->warn('⚠️  '.count($saldos).' saldo(s) no coinciden con el historial:');
        $this->table(['Producto', 'Donde', 'Esperado', 'Encontrado', 'Que significa'], $saldos);

        if ($this->option('corregir')) {
            $this->newLine();
            $this->corregirSaldos($stocks);

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Para cuadrarlos dejando constancia: <info>--corregir</info>');

        return self::FAILURE;
    }

    /**
     * Agrega un movimiento de ajuste por cada saldo que no cuadra.
     *
     * @param  \Illuminate\Support\Collection<int, Stock>  $stocks
     */
    private function corregirSaldos($stocks): void
    {
        $corregidos = 0;

        foreach ($stocks as $stock) {
            $ultimo = MovimientoStock::where('stock_id', $stock->stock_id)
                ->orderByDesc('fecha_movimiento')
                ->orderByDesc('id')
                ->first();

            $segunLibro = $ultimo?->stockNuevo ?? 0;
            $diferencia = $stock->cantidad_disponible - $segunLibro;

            if ($diferencia === 0) {
                continue;
            }

            $tipo = TipoMovimientoStock::where('signo', $diferencia > 0 ? 1 : -1)
                ->where('nombre', 'LIKE', '%Ajuste%')
                ->first()
                ?? TipoMovimientoStock::where('signo', $diferencia > 0 ? 1 : -1)->first();

            if (! $tipo) {
                $this->error('No hay un tipo de movimiento que sirva para el ajuste.');

                return;
            }

            MovimientoStock::create([
                'stock_id' => $stock->stock_id,
                'productoID' => $stock->productoID,
                'tipo_movimiento_id' => $tipo->id,
                'cantidad' => abs($diferencia),
                'stockAnterior' => $segunLibro,
                'stockNuevo' => $stock->cantidad_disponible,
                'motivo' => 'Correccion de inventario: el saldo real no coincidia con el historial',
                'referenciaTabla' => 'correccion',
                'user_id' => auth()->id() ?? 1,
                'fecha_movimiento' => now(),
            ]);

            $corregidos++;
        }

        $this->info("✅ Se registraron {$corregidos} movimientos de correccion. El saldo no se modifico.");
        $this->line('   Los cortes intermedios se dejan como estan: ya ocurrieron y borrarlos seria falsear la historia.');
    }
}
