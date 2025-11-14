<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\MovimientoCuentaCorriente;
use App\Models\TipoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckCuentasVencidasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para CU-09: Bloqueo Automático por Mora.
     */
    public function test_comando_bloquea_cliente_con_saldo_vencido()
    {
        // --- 1. ARRANGE ---

        $estadoActiva = EstadoCuentaCorriente::create(['nombreEstado' => 'Activa']);
        $estadoBloqueada = EstadoCuentaCorriente::create(['nombreEstado' => 'Bloqueada']);
        $estadoPendiente = EstadoCuentaCorriente::create(['nombreEstado' => 'Pendiente de Aprobación']);
        $estadoVencida = EstadoCuentaCorriente::create(['nombreEstado' => 'Vencida']);

        Configuracion::set('bloqueo_automatico_cc', 'true');
        Configuracion::set('dias_gracia_global', 7);
        Configuracion::set('limite_credito_global', 100000);

        $cuenta = CuentaCorriente::create([
            'saldo' => 50000,
            'limiteCredito' => 100000,
            'diasGracia' => 7,
            'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID,
        ]);

        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista']);
        $estadoCliActivo = EstadoCliente::create(['nombreEstado' => 'Activo']);

        $cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'DNI' => '12345678',
            'mail' => 'test@test.com',
            'whatsapp' => '54911123456',
            'telefono' => '123456',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => $estadoCliActivo->estadoClienteID,
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
        ]);

        MovimientoCuentaCorriente::create([
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
            'tipoMovimiento' => 'Debito',
            'monto' => 50000,
            'fechaEmision' => Carbon::now()->subDays(20),
            'fechaVencimiento' => Carbon::now()->subDays(13),
            'saldoAlMomento' => 50000,
            'descripcion' => 'Venta Vieja',
        ]);

        // --- 2. ACT ---

        $this->artisan('cuentas:check-vencidas')
            ->assertSuccessful();

        // --- 3. ASSERT ---

        $cuenta->refresh();

        // Verificación 1: Estado
        $this->assertEquals(
            $estadoBloqueada->estadoCuentaCorrienteID,
            $cuenta->estadoCuentaCorrienteID,
            "La cuenta debería haber pasado a 'Bloqueada'."
        );

        // Verificación 2: Auditoría
        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_MODIFICAR_ESTADO_CC,
            'tabla_afectada' => 'cuentas_corriente',
            'registro_id' => $cuenta->cuentaCorrienteID,
            'usuarioID' => null,

            // -----------------------------------------------------
            // CORRECCIÓN FINAL: Agregamos .00 al Total para coincidir con la BD
            'motivo' => 'Saldo Vencido: 50000 | Total: 50000.00 (Límite: 100000)',
            // -----------------------------------------------------

            'detalles' => "Cuenta Corriente {$cuenta->cuentaCorrienteID} bloqueada.",
        ]);
    }
}
