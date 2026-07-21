<?php

namespace Tests\Feature\Reparaciones;

use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Deposito;
use App\Models\EstadoProducto;
use App\Models\Marca;
use App\Models\MedioPago;
use App\Models\Modelo;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\Rol;
use App\Models\Stock;
use App\Models\TipoCliente;
use App\Models\TipoMovimientoStock;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReparacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Cliente $clienteMinorista;

    protected Cliente $clienteMayorista;

    protected Marca $marca;

    protected Modelo $modelo;

    protected Producto $repuesto;

    protected MedioPago $medioPagoEfectivo;

    protected MedioPago $medioPagoCC;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();

        // Rol + usuario
        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);
        // empresa_id=1 para reflejar el multi-tenant: el usuario pertenece a la
        // empresa por defecto, igual que las reparaciones que crea el test.
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id, 'empresa_id' => 1]);

        // Estados de reparación (6 estados fijos, PK auto-increment)
        DB::table('estados_reparacion')->insert([
            ['estadoReparacionID' => 1, 'nombreEstado' => 'Recibido', 'descripcion' => 'Ingresado', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 2, 'nombreEstado' => 'En Reparación', 'descripcion' => 'En curso', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 3, 'nombreEstado' => 'Espera de Repuesto', 'descripcion' => 'Pausa SLA', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 4, 'nombreEstado' => 'Reparado', 'descripcion' => 'Listo', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 5, 'nombreEstado' => 'Entregado', 'descripcion' => 'Final', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 6, 'nombreEstado' => 'Anulado', 'descripcion' => 'Cancelado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Marca + Modelo
        $this->marca = Marca::create(['nombre' => 'Samsung', 'activo' => true]);
        $this->modelo = Modelo::create(['marca_id' => $this->marca->id, 'nombre' => 'Galaxy S24', 'activo' => true]);

        // Paramétricas producto
        $categoria = CategoriaProducto::create(['nombre' => 'Repuestos', 'activo' => true]);
        $estadoProd = EstadoProducto::create(['nombre' => 'Activo', 'descripcion' => 'OK']);
        $unidad = UnidadMedida::create(['nombre' => 'Unidad', 'abreviatura' => 'u', 'activo' => true]);
        $deposito = Deposito::create(['nombre' => 'Depósito Principal', 'activo' => true, 'esPrincipal' => true]);

        // Tipos de movimiento de stock
        TipoMovimientoStock::create(['nombre' => 'Salida (Venta)', 'signo' => -1, 'activo' => true]);

        // Repuesto con stock
        $this->repuesto = Producto::create([
            'codigo' => 'REP-001',
            'nombre' => 'Pantalla Galaxy S24',
            'categoriaProductoID' => $categoria->id,
            'estadoProductoID' => $estadoProd->id,
            'unidad_medida_id' => $unidad->id,
            'precio_costo' => 30000,
        ]);
        Stock::create([
            'productoID' => $this->repuesto->id,
            'deposito_id' => $deposito->deposito_id,
            'cantidad_disponible' => 10,
            'stock_minimo' => 2,
        ]);

        $tipoMinorista = TipoCliente::create(['nombreTipo' => 'Minorista', 'descripcion' => 'General', 'activo' => true]);
        $tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista', 'descripcion' => 'Empresa', 'activo' => true]);

        PrecioProducto::create([
            'productoID' => $this->repuesto->id,
            'tipoClienteID' => $tipoMinorista->tipoClienteID,
            'precio' => 50000,
            'fechaDesde' => now()->subMonth(),
            'fechaHasta' => null,
        ]);

        // Medios de pago
        $this->medioPagoEfectivo = MedioPago::create(['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]);
        $this->medioPagoCC = MedioPago::create(['nombre' => 'Cuenta Corriente', 'recargo_porcentaje' => 0, 'activo' => true]);

        // Estados de cliente
        DB::table('estados_cliente')->insert([
            ['estadoClienteID' => 1, 'nombreEstado' => 'Activo', 'descripcion' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
            ['estadoClienteID' => 2, 'nombreEstado' => 'Inactivo', 'descripcion' => 'Inactivo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Estado CC
        DB::table('estados_cuenta_corriente')->insert([
            ['estadoCuentaCorrienteID' => 1, 'nombreEstado' => 'Activa', 'descripcion' => 'Activa', 'created_at' => now(), 'updated_at' => now()],
            ['estadoCuentaCorrienteID' => 2, 'nombreEstado' => 'Bloqueada', 'descripcion' => 'Bloqueada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Cliente minorista (sin CC)
        $this->clienteMinorista = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'DNI' => '33444555',
            'mail' => 'juan@test.com',
            'tipoClienteID' => $tipoMinorista->tipoClienteID,
            'estadoClienteID' => 1,
        ]);

        // Cliente mayorista con CC
        $cc = CuentaCorriente::create([
            'saldo' => 0,
            'limiteCredito' => 500000,
            'diasGracia' => 30,
            'estadoCuentaCorrienteID' => 1,
        ]);
        $this->clienteMayorista = Cliente::create([
            'nombre' => 'María',
            'apellido' => 'López',
            'DNI' => '27888999',
            'mail' => 'maria@empresa.com',
            'tipoClienteID' => $tipoMayorista->tipoClienteID,
            'estadoClienteID' => 1,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);
    }

    // ─── CU-11: REGISTRAR REPARACIÓN ───────────────────────────

    /** @test */
    public function puede_registrar_reparacion_basica()
    {
        $response = $this->actingAs($this->admin)->post(route('reparaciones.store'), [
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'marca_id' => $this->marca->id,
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Pantalla rota por caída',
            'costo_mano_obra' => 5000,
            'total_final' => 55000,
        ]);

        $reparacion = Reparacion::first();
        $this->assertNotNull($reparacion);

        $response->assertRedirect(route('reparaciones.show', $reparacion->reparacionID));
        $response->assertSessionHas('success');

        // Estado inicial = Recibido (ID 1)
        $this->assertEquals(1, $reparacion->estado_reparacion_id);
        $this->assertEquals($this->clienteMinorista->clienteID, $reparacion->clienteID);
        $this->assertStringStartsWith('REP-', $reparacion->codigo_reparacion);
        $this->assertEquals('Pantalla rota por caída', $reparacion->falla_declarada);
    }

    /** @test */
    public function puede_registrar_reparacion_con_repuestos_y_descuenta_stock()
    {
        $response = $this->actingAs($this->admin)->post(route('reparaciones.store'), [
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'marca_id' => $this->marca->id,
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Pantalla rota',
            'costo_mano_obra' => 5000,
            'total_final' => 55000,
            'items' => [
                ['producto_id' => $this->repuesto->id, 'cantidad' => 1],
            ],
        ]);

        $response->assertSessionHas('success');

        // Stock decrementado
        $stock = Stock::where('productoID', $this->repuesto->id)->first();
        $this->assertEquals(9, $stock->cantidad_disponible); // 10 - 1

        // Detalle de reparación creado
        $reparacion = Reparacion::first();
        $this->assertCount(1, $reparacion->repuestos);
        $this->assertEquals($this->repuesto->id, $reparacion->repuestos->first()->producto_id);
    }

    /** @test */
    public function validacion_requiere_campos_obligatorios()
    {
        $response = $this->actingAs($this->admin)->post(route('reparaciones.store'), []);

        $response->assertSessionHasErrors(['clienteID', 'tecnico_id', 'marca_id', 'modelo_id', 'falla_declarada']);
    }

    /** @test */
    public function genera_comprobante_ingreso_al_registrar()
    {
        // Crear tipos de comprobante y estados necesarios
        DB::table('tipos_comprobante')->insertOrIgnore([
            'tipo_id' => 100, 'codigo' => 'INGRESO_REPARACION', 'nombre' => 'Ingreso de Reparación',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('estados_comprobante')->insertOrIgnore([
            'estado_id' => 100, 'nombre' => 'EMITIDO', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->post(route('reparaciones.store'), [
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'marca_id' => $this->marca->id,
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'No enciende',
            'total_final' => 10000,
        ]);

        $reparacion = Reparacion::first();
        $this->assertNotNull($reparacion);

        // Verificar comprobante
        $comprobante = DB::table('comprobantes')
            ->where('tipo_entidad', (new Reparacion)->getMorphClass())
            ->where('entidad_id', $reparacion->reparacionID)
            ->first();
        $this->assertNotNull($comprobante, 'Debe generar comprobante INGRESO_REPARACION');
        $this->assertStringContains('ING-', $comprobante->numero_correlativo);
    }

    // ─── CU-12: ACTUALIZAR REPARACIÓN (TRANSICIONES DE ESTADO) ─

    /** @test */
    public function puede_cambiar_estado_recibido_a_en_reparacion()
    {
        // Crear reparación en estado Recibido
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 1, // Recibido
            'codigo_reparacion' => 'REP-TEST-001',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'No carga',
            'fecha_ingreso' => now(),
            'total_final' => 10000,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('reparaciones.update', $reparacion->reparacionID),
            [
                'estado_reparacion_id' => 2, // En Reparación
                'diagnostico_tecnico' => 'Puerto de carga dañado',
            ]
        );

        $response->assertRedirect(route('reparaciones.show', $reparacion->reparacionID));
        $response->assertSessionHas('success');

        $reparacion->refresh();
        $this->assertEquals(2, $reparacion->estado_reparacion_id);
        $this->assertEquals('Puerto de carga dañado', $reparacion->diagnostico_tecnico);
    }

    /** @test */
    public function no_permite_transicion_de_estado_invalida()
    {
        // Reparación en estado Espera de Repuesto (3)
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 3, // Espera de Repuesto
            'codigo_reparacion' => 'REP-TEST-002',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Placa dañada',
            'fecha_ingreso' => now(),
            'total_final' => 20000,
        ]);

        // Intentar pasar a Recibido (1) — no es transición válida desde 3
        $response = $this->actingAs($this->admin)->put(
            route('reparaciones.update', $reparacion->reparacionID),
            ['estado_reparacion_id' => 1]
        );

        $response->assertSessionHasErrors();
        $reparacion->refresh();
        $this->assertEquals(3, $reparacion->estado_reparacion_id); // Sin cambiar
    }

    /** @test */
    public function no_permite_modificar_reparacion_en_estado_final()
    {
        // Reparación Entregada (estado final 5)
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 5, // Entregado
            'codigo_reparacion' => 'REP-TEST-003',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Batería',
            'fecha_ingreso' => now(),
            'fecha_entrega_real' => now(),
            'total_final' => 15000,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('reparaciones.update', $reparacion->reparacionID),
            ['estado_reparacion_id' => 2]
        );

        $response->assertSessionHasErrors();
        $reparacion->refresh();
        $this->assertEquals(5, $reparacion->estado_reparacion_id); // Sigue en Entregado
    }

    // ─── CU-13: COBRAR REPARACIÓN ──────────────────────────────

    /** @test */
    public function puede_cobrar_reparacion_en_estado_reparado()
    {
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 4, // Reparado
            'codigo_reparacion' => 'REP-TEST-004',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Pantalla',
            'fecha_ingreso' => now(),
            'total_final' => 55000,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('reparaciones.cobrar', $reparacion->reparacionID),
            ['medio_pago_id' => $this->medioPagoEfectivo->medioPagoID]
        );

        $response->assertRedirect(route('reparaciones.show', $reparacion->reparacionID));
        $response->assertSessionHas('success');

        $reparacion->refresh();
        $this->assertEquals('pagado', $reparacion->estado_pago);
        $this->assertEquals(55000, (float) $reparacion->monto_cobrado);
        // Cobrar NO cambia estado — sigue en Reparado (4).
        // La entrega se confirma manualmente cuando el cliente retira el equipo.
        $this->assertEquals(4, $reparacion->estado_reparacion_id);
        $this->assertNull($reparacion->fecha_entrega_real);
    }

    /** @test */
    public function puede_cobrar_reparacion_con_cuenta_corriente()
    {
        // Tipo movimiento CC
        DB::table('tipos_movimiento_cuenta_corriente')->insertOrIgnore([
            ['tipo_id' => 1, 'nombre' => 'Debito', 'multiplicador' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_id' => 2, 'nombre' => 'Credito', 'multiplicador' => -1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMayorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 4, // Reparado
            'codigo_reparacion' => 'REP-TEST-005',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'WiFi',
            'fecha_ingreso' => now(),
            'total_final' => 30000,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('reparaciones.cobrar', $reparacion->reparacionID),
            ['medio_pago_id' => $this->medioPagoCC->medioPagoID]
        );

        $response->assertRedirect(route('reparaciones.show', $reparacion->reparacionID));

        $reparacion->refresh();
        $this->assertEquals('cuenta_corriente', $reparacion->estado_pago);

        // CC debited
        $cc = CuentaCorriente::find($this->clienteMayorista->cuentaCorrienteID);
        $this->assertEquals(30000, (float) $cc->saldo);
    }

    /** @test */
    public function no_permite_cobrar_reparacion_ya_cobrada()
    {
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 5,
            'codigo_reparacion' => 'REP-TEST-006',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Micrófono',
            'fecha_ingreso' => now(),
            'fecha_entrega_real' => now(),
            'total_final' => 10000,
            'estado_pago' => 'pagado',
            'monto_cobrado' => 10000,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'fecha_cobro' => now(),
            'cobrado_por' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('reparaciones.cobrar', $reparacion->reparacionID),
            ['medio_pago_id' => $this->medioPagoEfectivo->medioPagoID]
        );

        $response->assertSessionHasErrors('cobro');
    }

    /** @test */
    public function no_permite_cobrar_reparacion_anulada()
    {
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 6, // Anulado
            'codigo_reparacion' => 'REP-TEST-007',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Speaker',
            'fecha_ingreso' => now(),
            'total_final' => 8000,
            'anulada' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('reparaciones.cobrar', $reparacion->reparacionID),
            ['medio_pago_id' => $this->medioPagoEfectivo->medioPagoID]
        );

        $response->assertSessionHasErrors('cobro');
    }

    // ─── MULTI-TENANT Y AUTORIZACIÓN ────────────────────────────

    /** @test */
    public function una_reparacion_de_otra_empresa_no_es_accesible()
    {
        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $this->admin->id,
            'estado_reparacion_id' => 1,
            'codigo_reparacion' => 'REP-OTRA-EMP',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Falla',
            'fecha_ingreso' => now(),
        ]);

        // La movemos a otra empresa (bypass del scope, a nivel de query builder).
        $otraEmpresa = \App\Models\Empresa::create(['nombre' => 'Otra', 'slug' => 'otra-'.uniqid()]);
        DB::table('reparaciones')->where('reparacionID', $reparacion->reparacionID)
            ->update(['empresa_id' => $otraEmpresa->id]);

        // El admin (empresa 1) no debe poder verla.
        $this->actingAs($this->admin)
            ->get(route('reparaciones.show', $reparacion->reparacionID))
            ->assertNotFound();
    }

    /** @test */
    public function un_tecnico_no_puede_cobrar_ni_anular()
    {
        $rolTecnico = Rol::firstOrCreate(
            ['nombre' => 'tecnico', 'empresa_id' => 1],
            ['descripcion' => 'Técnico', 'permisos' => [], 'activo' => true],
        );
        $tecnico = User::factory()->create(['rol_id' => $rolTecnico->rol_id, 'empresa_id' => 1]);

        $reparacion = Reparacion::create([
            'clienteID' => $this->clienteMinorista->clienteID,
            'tecnico_id' => $tecnico->id,
            'estado_reparacion_id' => 4,
            'codigo_reparacion' => 'REP-TEC-001',
            'modelo_id' => $this->modelo->id,
            'falla_declarada' => 'Falla',
            'fecha_ingreso' => now(),
            'total_final' => 10000,
        ]);

        // Cobrar → prohibido para técnico.
        $this->actingAs($tecnico)->post(
            route('reparaciones.cobrar', $reparacion->reparacionID),
            ['medio_pago_id' => $this->medioPagoEfectivo->medioPagoID]
        )->assertForbidden();

        // Anular → prohibido para técnico.
        $this->actingAs($tecnico)->delete(
            route('reparaciones.destroy', $reparacion->reparacionID),
            ['motivo' => 'prueba de permisos denegada']
        )->assertForbidden();
    }

    // ─── HELPER ─────────────────────────────────────────────────

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
