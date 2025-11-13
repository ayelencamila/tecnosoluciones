<?php

// database/migrations/..._create_movimientos_cuenta_corriente_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->id('movimientoCCID'); // Clave primaria
            $table->foreignId('cuentaCorrienteID')->constrained('cuentas_corriente', 'cuentaCorrienteID'); // FK a CuentaCorriente

            $table->enum('tipoMovimiento', ['Debito', 'Credito']); // 'Debito' (Venta/Recargo), 'Credito' (Pago/NotaCredito)
            $table->string('descripcion'); // Descripción del movimiento (ej: "Venta N° 123", "Pago N° 456")
            $table->decimal('monto', 10, 2); // Monto del movimiento (siempre positivo, el tipoMovimiento define si suma o resta)

            $table->date('fechaEmision'); // Fecha en que se generó el movimiento (ej: fecha de la venta)
            $table->date('fechaVencimiento')->nullable(); // Fecha de vencimiento si aplica (principalmente para débitos)

            $table->decimal('saldoAlMomento', 10, 2); // Saldo de la CC justo después de este movimiento

            // Referencia al origen del movimiento (ej: una Venta, un Pago, una Nota de Crédito)
            $table->unsignedBigInteger('referenciaID')->nullable();
            $table->string('referenciaTabla')->nullable(); // Ej: 'ventas', 'pagos', 'notas_credito'

            $table->text('observaciones')->nullable(); // Notas adicionales

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta_corriente');
    }
};
