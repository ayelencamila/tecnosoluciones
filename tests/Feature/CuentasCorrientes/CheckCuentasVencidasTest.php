<?php

namespace Tests\Feature\CuentasCorrientes;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\MovimientoCuentaCorriente;
use App\Models\TipoCliente;
use App\Jobs\NotificarIncumplimientoCC;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CheckCuentasVencidasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para CU-09: Bloqueo Automático por Mora.
     * 
     * Escenario: Cliente mayorista con saldo vencido debe ser bloqueado
     * automáticamente al ejecutar el comando de verificación.
     */
    public function test_comando_bloquea_cliente_con_saldo_vencido()
    {
        // --- 1. ARRANGE ---
        Queue::fake();

        // Estados de cuenta corriente
        $estadoActiva = EstadoCuentaCorriente::create(['nombreEstado' => 'Activa']);
        $estadoBloqueada = EstadoCuentaCorriente::create(['nombreEstado' => 'Bloqueada']);
        EstadoCuentaCorriente::create(['nombreEstado' => 'Pendiente de Aprobación']);

        // Estado cliente
        $estadoCliActivo = EstadoCliente::create(['nombreEstado' => 'Activo']);
        EstadoCliente::create(['nombreEstado' => 'Moroso']);

        // Tipo cliente mayorista
        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista']);

        // Obtener tipo de movimiento Débito (viene de la migración)
        $tipoDebito = DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Debito')
            ->value('tipo_id');

        // Configuración del sistema
        Configuracion::set('bloqueo_automatico_cc', 'true');
        Configuracion::set('dias_gracia_global', 7);
        Configuracion::set('limite_credito_global', 100000);

        // Crear cuenta corriente activa
        $cuenta = CuentaCorriente::create([
            'saldo' => 50000,
            'limiteCredito' => 100000,
            'diasGracia' => 7,
            'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID,
        ]);

        // Crear cliente mayorista con cuenta corriente
        $cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'DNI' => '12345678',
            'mail' => 'test@test.com',
            'whatsapp' => '+5491112345678',
            'telefono' => '123456',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => $estadoCliActivo->estadoClienteID,
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
        ]);

        // Crear movimiento vencido (emitido hace 20 días, vencido hace 13 días)
        // Con 7 días de gracia, está vencido hace 6 días reales
        MovimientoCuentaCorriente::create([
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
            'tipo_movimiento_cc_id' => $tipoDebito,
            'monto' => 50000,
            'fechaEmision' => Carbon::now()->subDays(20),
            'fechaVencimiento' => Carbon::now()->subDays(13),
            'saldoAlMomento' => 50000,
            'descripcion' => 'Venta a crédito - Test CU-09',
        ]);

        // --- 2. ACT ---
        $this->artisan('cc:check-vencimientos')
            ->assertSuccessful();

        // --- 3. ASSERT ---
        $cuenta->refresh();

        // Verificación 1: Estado de la cuenta debe ser Bloqueada
        $this->assertEquals(
            $estadoBloqueada->estadoCuentaCorrienteID,
            $cuenta->estadoCuentaCorrienteID,
            "La cuenta debería haber pasado a 'Bloqueada'."
        );

        // Verificación 2: Debe haber registro de auditoría de bloqueo
        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_BLOQUEAR_CC,
            'tabla_afectada' => 'cuentas_corriente',
            'registro_id' => $cuenta->cuentaCorrienteID,
        ]);

        // Verificación 3: Debe haberse encolado notificación
        Queue::assertPushed(NotificarIncumplimientoCC::class);
    }

    /**
     * Test: Cuenta sin saldos vencidos NO debe ser bloqueada
     */
    public function test_cuenta_sin_vencidos_no_se_bloquea()
    {
        // --- ARRANGE ---
        Queue::fake();

        $estadoActiva = EstadoCuentaCorriente::create(['nombreEstado' => 'Activa']);
        EstadoCuentaCorriente::create(['nombreEstado' => 'Bloqueada']);

        $estadoCliActivo = EstadoCliente::create(['nombreEstado' => 'Activo']);
        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista']);

        $tipoDebito = DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Debito')
            ->value('tipo_id');

        Configuracion::set('bloqueo_automatico_cc', 'true');
        Configuracion::set('dias_gracia_global', 7);
        Configuracion::set('limite_credito_global', 100000);

        $cuenta = CuentaCorriente::create([
            'saldo' => 30000,
            'limiteCredito' => 100000,
            'diasGracia' => 7,
            'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Maria',
            'apellido' => 'Garcia',
            'DNI' => '87654321',
            'mail' => 'maria@test.com',
            'whatsapp' => '+5491187654321',
            'telefono' => '654321',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => $estadoCliActivo->estadoClienteID,
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
        ]);

        // Movimiento NO vencido (vence en el futuro)
        MovimientoCuentaCorriente::create([
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
            'tipo_movimiento_cc_id' => $tipoDebito,
            'monto' => 30000,
            'fechaEmision' => Carbon::now()->subDays(5),
            'fechaVencimiento' => Carbon::now()->addDays(25), // Vence en 25 días
            'saldoAlMomento' => 30000,
            'descripcion' => 'Venta reciente - No vencida',
        ]);

        // --- ACT ---
        $this->artisan('cc:check-vencimientos')
            ->assertSuccessful();

        // --- ASSERT ---
        $cuenta->refresh();

        // Debe seguir activa
        $this->assertEquals(
            $estadoActiva->estadoCuentaCorrienteID,
            $cuenta->estadoCuentaCorrienteID,
            "La cuenta NO debería bloquearse si no tiene saldos vencidos."
        );

        // NO debe haber registro de bloqueo
        $this->assertDatabaseMissing('auditorias', [
            'accion' => Auditoria::ACCION_BLOQUEAR_CC,
            'registro_id' => $cuenta->cuentaCorrienteID,
        ]);
    }

    /**
     * Test: Con bloqueo automático OFF, la cuenta va a "Pendiente de Aprobación"
     */
    public function test_sin_bloqueo_automatico_pone_pendiente_aprobacion()
    {
        // --- ARRANGE ---
        Queue::fake();

        $estadoActiva = EstadoCuentaCorriente::create(['nombreEstado' => 'Activa']);
        EstadoCuentaCorriente::create(['nombreEstado' => 'Bloqueada']);
        $estadoPendiente = EstadoCuentaCorriente::create(['nombreEstado' => 'Pendiente de Aprobación']);

        $estadoCliActivo = EstadoCliente::create(['nombreEstado' => 'Activo']);
        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista']);

        $tipoDebito = DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Debito')
            ->value('tipo_id');

        // BLOQUEO AUTOMÁTICO DESACTIVADO
        Configuracion::set('bloqueo_automatico_cc', false);
        // Limpiar cache para asegurar que tome el nuevo valor
        cache()->forget('config:bloqueo_automatico_cc');
        
        Configuracion::set('dias_gracia_global', 7);
        Configuracion::set('limite_credito_global', 100000);

        $cuenta = CuentaCorriente::create([
            'saldo' => 50000,
            'limiteCredito' => 100000,
            'diasGracia' => 7,
            'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Pedro',
            'apellido' => 'Lopez',
            'DNI' => '11223344',
            'mail' => 'pedro@test.com',
            'whatsapp' => '+5491122334455',
            'telefono' => '112233',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => $estadoCliActivo->estadoClienteID,
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
        ]);

        // Movimiento VENCIDO
        MovimientoCuentaCorriente::create([
            'cuentaCorrienteID' => $cuenta->cuentaCorrienteID,
            'tipo_movimiento_cc_id' => $tipoDebito,
            'monto' => 50000,
            'fechaEmision' => Carbon::now()->subDays(20),
            'fechaVencimiento' => Carbon::now()->subDays(13),
            'saldoAlMomento' => 50000,
            'descripcion' => 'Venta vencida - Test sin bloqueo auto',
        ]);

        // --- ACT ---
        $this->artisan('cc:check-vencimientos')
            ->assertSuccessful();

        // --- ASSERT ---
        $cuenta->refresh();

        // Debe estar en "Pendiente de Aprobación", NO bloqueada
        $this->assertEquals(
            $estadoPendiente->estadoCuentaCorrienteID,
            $cuenta->estadoCuentaCorrienteID,
            "Sin bloqueo automático, la cuenta debe quedar 'Pendiente de Aprobación'."
        );
    }
}
