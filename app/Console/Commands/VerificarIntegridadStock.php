<?php

namespace App\Console\Commands;

use App\Console\Concerns\EjecutaPorEmpresa;
use App\Models\MovimientoStock;
use App\Models\Stock;
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

    protected $signature = 'stock:verificar-integridad {--empresa= : Revisar solo una empresa}';

    protected $description = 'Compara el historial de movimientos con el saldo de stock y reporta diferencias';

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
        $problemas = [];

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
                    $problemas[] = [$producto, 'sin movimientos', '-', $stock->cantidad_disponible,
                        'Hay existencias que ningun movimiento explica'];
                }

                continue;
            }

            // 1. Continuidad de la cadena
            $esperado = null;

            foreach ($movimientos as $mov) {
                if ($esperado !== null && $mov->stockAnterior !== $esperado) {
                    $problemas[] = [$producto, "movimiento #{$mov->id}", $esperado, $mov->stockAnterior,
                        'El saldo cambio entre dos movimientos sin registrarse'];
                }

                $esperado = $mov->stockNuevo;
            }

            // 2. Saldo final
            if ($stock->cantidad_disponible !== $esperado) {
                $problemas[] = [$producto, 'saldo actual', $esperado, $stock->cantidad_disponible,
                    'El saldo no coincide con el ultimo movimiento'];
            }
        }

        if (empty($problemas)) {
            $this->info('✅ El historial de movimientos coincide con el stock en todos los productos.');

            return self::SUCCESS;
        }

        $this->warn('⚠️  Se encontraron '.count($problemas).' diferencias entre el historial y el stock:');
        $this->newLine();
        $this->table(['Producto', 'Donde', 'Esperado', 'Encontrado', 'Que significa'], $problemas);

        return self::FAILURE;
    }
}
