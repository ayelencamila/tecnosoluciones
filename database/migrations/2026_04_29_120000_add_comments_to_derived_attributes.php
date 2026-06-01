<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Documenta atributos derivados (Elmasri, "Fundamentos de Sistemas de Bases
 * de Datos", 5ª Ed., Cap. 7 - Modelo Relacional).
 *
 * Un atributo derivado es aquel cuyo valor puede calcularse a partir de
 * otros datos ya almacenados en la base. Persistirlo es una desnormalización
 * controlada: se acepta la redundancia para optimizar consultas históricas
 * y evitar joins costosos sobre tablas con muchos registros.
 *
 * Esta migración no altera tipos ni constraints: solo agrega COMMENT a
 * cada columna derivada para que quede documentado a nivel del catálogo
 * del SGBD.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->columnasDerivadas() as [$tabla, $columna, $tipoSql, $comentario]) {
            DB::statement("ALTER TABLE `{$tabla}` MODIFY COLUMN `{$columna}` {$tipoSql} COMMENT " . $this->quote($comentario));
        }
    }

    public function down(): void
    {
        foreach ($this->columnasDerivadas() as [$tabla, $columna, $tipoSql]) {
            DB::statement("ALTER TABLE `{$tabla}` MODIFY COLUMN `{$columna}` {$tipoSql} COMMENT ''");
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function columnasDerivadas(): array
    {
        return [
            ['ventas', 'subtotal', 'DECIMAL(15,2) NOT NULL DEFAULT 0',
                '[Derivado - Elmasri Cap.7] = SUM(detalle_ventas.subtotal_neto) WHERE venta_id = ventas.venta_id'],

            ['ventas', 'total_descuentos', 'DECIMAL(15,2) NOT NULL DEFAULT 0',
                '[Derivado - Elmasri Cap.7] = SUM(descuento_venta) + SUM(descuento_detalle_venta) aplicados a la venta'],

            ['ventas', 'total', 'DECIMAL(15,2) NOT NULL DEFAULT 0',
                '[Derivado - Elmasri Cap.7] = subtotal - total_descuentos'],

            ['detalle_ventas', 'subtotal', 'DECIMAL(15,2) NOT NULL',
                '[Derivado - Elmasri Cap.7] = cantidad * precio_unitario'],

            ['detalle_ventas', 'subtotal_neto', 'DECIMAL(15,2) NOT NULL',
                '[Derivado - Elmasri Cap.7] = subtotal - descuento_item'],

            ['reparaciones', 'total_final', 'DECIMAL(10,2) NOT NULL DEFAULT 0',
                '[Derivado - Elmasri Cap.7] = costo_mano_obra + SUM(detalle_reparaciones) - bonificaciones aplicadas'],

            ['cuentas_corriente', 'saldo', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
                '[Derivado - Elmasri Cap.7] = SUM(movimientos_cuenta_corriente.monto) segun tipo Debito/Credito'],
        ];
    }

    private function quote(string $valor): string
    {
        return "'" . str_replace("'", "''", $valor) . "'";
    }
};
