<?php

namespace Tests\Feature\Pagos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\Rol;
use App\Models\Cliente;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\MedioPago;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\EstadoVenta;
use App\Events\PagoRegistrado;
use Illuminate\Support\Facades\DB;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Cliente $clienteMayorista;
    protected CuentaCorriente $cc;
    protected MedioPago $medioPagoEfectivo;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake([PagoRegistrado::class]);

        // Rol + usuario
        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Tipo de cliente
        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista', 'descripcion' => 'Empresa', 'activo' => true]);

        // Estados
        DB::table('estados_cliente')->insert([
            ['estadoClienteID' => 1, 'nombreEstado' => 'Activo', 'descripcion' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('estados_cuenta_corriente')->insert([
            ['estadoCuentaCorrienteID' => 1, 'nombreEstado' => 'Activa', 'descripcion' => 'Activa', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tipos movimiento CC
        DB::table('tipos_movimiento_cuenta_corriente')->insertOrIgnore([
            ['tipo_id' => 1, 'nombre' => 'Debito', 'multiplicador' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_id' => 2, 'nombre' => 'Credito', 'multiplicador' => -1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tipos/estados de comprobante — usar updateOrInsert para evitar conflictos
        DB::table('tipos_comprobante')->updateOrInsert(
            ['codigo' => 'RECIBO_PAGO'],
            ['nombre' => 'Recibo de Pago', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('estados_comprobante')->updateOrInsert(
            ['nombre' => 'EMITIDO'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Medio de pago
        $this->medioPagoEfectivo = MedioPago::create(['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]);

        // CC + cliente mayorista con deuda
        $this->cc = CuentaCorriente::create([
            'saldo' => 50000, // Tiene deuda de 50.000
            'limiteCredito' => 500000,
            'diasGracia' => 30,
            'estadoCuentaCorrienteID' => 1,
        ]);
        $this->clienteMayorista = Cliente::create([
            'nombre' => 'Empresa',
            'apellido' => 'Test',
            'DNI' => '20777888',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => 1,
            'cuentaCorrienteID' => $this->cc->cuentaCorrienteID,
        ]);
    }

    // ─── CU-10: REGISTRAR PAGO ──────────────────────────────────

    /** @test */
    public function puede_registrar_pago_y_acredita_en_cuenta_corriente()
    {
        $response = $this->actingAs($this->admin)->post(route('pagos.store'), [
            'clienteID' => $this->clienteMayorista->clienteID,
            'monto' => 20000,
            'medioPagoID' => $this->medioPagoEfectivo->medioPagoID,
        ]);

        $response->assertSessionDoesntHaveErrors();

        // Pago creado
        $pago = Pago::first();
        $this->assertNotNull($pago);
        $this->assertEquals(20000, (float) $pago->monto);
        $this->assertEquals($this->clienteMayorista->clienteID, $pago->clienteID);
        $this->assertNotNull($pago->numero_recibo);
        $this->assertStringStartsWith('REC-', $pago->numero_recibo);

        // CC acreditada: saldo baja de 50000 a 30000
        $this->cc->refresh();
        $this->assertEquals(30000, (float) $this->cc->saldo);
    }

    /** @test */
    public function pago_genera_comprobante_recibo()
    {
        $this->actingAs($this->admin)->post(route('pagos.store'), [
            'clienteID' => $this->clienteMayorista->clienteID,
            'monto' => 10000,
            'medioPagoID' => $this->medioPagoEfectivo->medioPagoID,
        ]);

        $pago = Pago::first();

        $comprobante = DB::table('comprobantes')
            ->where('tipo_entidad', (new Pago)->getMorphClass())
            ->where('entidad_id', $pago->pagoID)
            ->first();

        $this->assertNotNull($comprobante, 'Debe generar comprobante RECIBO_PAGO');
    }

    /** @test */
    public function validacion_requiere_campos_obligatorios_pago()
    {
        $response = $this->actingAs($this->admin)->post(route('pagos.store'), []);

        $response->assertSessionHasErrors(['clienteID', 'monto', 'medioPagoID']);
    }

    /** @test */
    public function no_permite_monto_cero_o_negativo()
    {
        $response = $this->actingAs($this->admin)->post(route('pagos.store'), [
            'clienteID' => $this->clienteMayorista->clienteID,
            'monto' => 0,
            'medioPagoID' => $this->medioPagoEfectivo->medioPagoID,
        ]);

        $response->assertSessionHasErrors('monto');
    }

    // ─── AUTO-IMPUTACIÓN (CU-10 Paso 6) ────────────────────────

    /** @test */
    public function pago_se_imputa_automaticamente_a_ventas_pendientes()
    {
        // Estados de venta
        DB::table('estados_venta')->insert([
            ['estadoVentaID' => 1, 'nombreEstado' => 'Pendiente', 'created_at' => now(), 'updated_at' => now()],
            ['estadoVentaID' => 2, 'nombreEstado' => 'Completada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Crear venta pendiente
        $venta = Venta::create([
            'clienteID' => $this->clienteMayorista->clienteID,
            'estado_venta_id' => 1,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'user_id' => $this->admin->id,
            'total' => 25000,
            'fecha_venta' => now()->subDays(5),
            'numero_comprobante' => 'VTA-TEST-001',
        ]);

        $this->actingAs($this->admin)->post(route('pagos.store'), [
            'clienteID' => $this->clienteMayorista->clienteID,
            'monto' => 25000,
            'medioPagoID' => $this->medioPagoEfectivo->medioPagoID,
        ]);

        $pago = Pago::first();
        $this->assertNotNull($pago);

        // Verificar imputación
        $imputaciones = $pago->ventasImputadas;
        $this->assertGreaterThanOrEqual(1, $imputaciones->count());
    }
}
