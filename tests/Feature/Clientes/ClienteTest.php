<?php

namespace Tests\Feature\Clientes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\Rol;
use App\Models\Cliente;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\Direccion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Provincia;
use App\Models\Localidad;
use Illuminate\Support\Facades\DB;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected TipoCliente $tipoMinorista;
    protected TipoCliente $tipoMayorista;
    protected Provincia $provincia;
    protected Localidad $localidad;

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
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Tipos de cliente
        $this->tipoMinorista = TipoCliente::create(['nombreTipo' => 'Minorista', 'descripcion' => 'Público general', 'activo' => true]);
        $this->tipoMayorista = TipoCliente::create(['nombreTipo' => 'Mayorista', 'descripcion' => 'Empresa', 'activo' => true]);

        // Estados de cliente (auto-increment PK)
        DB::table('estados_cliente')->insert([
            ['estadoClienteID' => 1, 'nombreEstado' => 'Activo', 'descripcion' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
            ['estadoClienteID' => 2, 'nombreEstado' => 'Inactivo', 'descripcion' => 'Inactivo', 'created_at' => now(), 'updated_at' => now()],
            ['estadoClienteID' => 3, 'nombreEstado' => 'Suspendido', 'descripcion' => 'Suspendido', 'created_at' => now(), 'updated_at' => now()],
            ['estadoClienteID' => 4, 'nombreEstado' => 'Moroso', 'descripcion' => 'Moroso', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Estados CC
        DB::table('estados_cuenta_corriente')->insert([
            ['estadoCuentaCorrienteID' => 1, 'nombreEstado' => 'Activa', 'descripcion' => 'Activa', 'created_at' => now(), 'updated_at' => now()],
            ['estadoCuentaCorrienteID' => 2, 'nombreEstado' => 'Bloqueada', 'descripcion' => 'Bloqueada', 'created_at' => now(), 'updated_at' => now()],
            ['estadoCuentaCorrienteID' => 3, 'nombreEstado' => 'Vencida', 'descripcion' => 'Vencida', 'created_at' => now(), 'updated_at' => now()],
            ['estadoCuentaCorrienteID' => 4, 'nombreEstado' => 'Cerrada', 'descripcion' => 'Cerrada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Ubicación geográfica
        $this->provincia = Provincia::create(['nombre' => 'Buenos Aires']);
        $this->localidad = Localidad::create([
            'nombre' => 'La Plata',
            'provinciaID' => $this->provincia->provinciaID,
        ]);
    }

    // ─── CU-01: REGISTRAR CLIENTE MINORISTA ────────────────────

    /** @test */
    public function puede_registrar_cliente_minorista()
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombre' => 'Carlos',
            'apellido' => 'García',
            'DNI' => '30555666',
            'mail' => 'carlos@test.com',
            'whatsapp' => '1155556666',
            'telefono' => '42223333',
            'calle' => 'Av. Siempreviva',
            'altura' => '742',
            'codigoPostal' => '1900',
            'provincia_id' => $this->provincia->provinciaID,
            'localidad_id' => $this->localidad->localidadID,
            'tipo_cliente_id' => $this->tipoMinorista->tipoClienteID,
            'estado_cliente_id' => 1, // Activo
        ]);

        $response->assertSessionDoesntHaveErrors();

        // Cliente creado
        $cliente = Cliente::where('DNI', '30555666')->first();
        $this->assertNotNull($cliente);
        $this->assertEquals('Carlos', $cliente->nombre);
        $this->assertEquals('García', $cliente->apellido);

        // Dirección creada
        $this->assertNotNull($cliente->direccionID);
        $direccion = Direccion::find($cliente->direccionID);
        $this->assertEquals('Av. Siempreviva', $direccion->calle);
        $this->assertEquals('742', $direccion->altura);

        // Minorista NO tiene cuenta corriente
        $this->assertNull($cliente->cuentaCorrienteID);
    }

    // ─── CU-01: REGISTRAR CLIENTE MAYORISTA CON CC ──────────────

    /** @test */
    public function registrar_mayorista_crea_cuenta_corriente_automaticamente()
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombre' => 'Empresa',
            'apellido' => 'TecnoMax',
            'DNI' => '20999111',
            'mail' => 'empresa@tecnomax.com',
            'calle' => 'Industrial',
            'altura' => '500',
            'codigoPostal' => '1000',
            'provincia_id' => $this->provincia->provinciaID,
            'localidad_id' => $this->localidad->localidadID,
            'tipo_cliente_id' => $this->tipoMayorista->tipoClienteID,
            'estado_cliente_id' => 1,
            'limiteCredito' => 200000,
            'diasGracia' => 30,
        ]);

        $response->assertSessionDoesntHaveErrors();

        $cliente = Cliente::where('DNI', '20999111')->first();
        $this->assertNotNull($cliente);

        // Mayorista SÍ tiene cuenta corriente
        $this->assertNotNull($cliente->cuentaCorrienteID);

        $cc = CuentaCorriente::find($cliente->cuentaCorrienteID);
        $this->assertNotNull($cc);
        $this->assertEquals(0, (float) $cc->saldo);
        $this->assertEquals(200000, (float) $cc->limiteCredito);
        $this->assertEquals(30, $cc->diasGracia);
        $this->assertEquals(1, $cc->estadoCuentaCorrienteID); // Activa
    }

    // ─── VALIDACIÓN ─────────────────────────────────────────────

    /** @test */
    public function validacion_requiere_campos_obligatorios()
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), []);

        $response->assertSessionHasErrors([
            'nombre', 'apellido', 'DNI',
            'calle', 'altura', 'codigoPostal',
            'provincia_id', 'localidad_id',
            'tipo_cliente_id', 'estado_cliente_id',
        ]);
    }

    /** @test */
    public function no_permite_dni_duplicado()
    {
        // Crear primer cliente
        Cliente::create([
            'nombre' => 'Primer',
            'apellido' => 'Cliente',
            'DNI' => '11222333',
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'estadoClienteID' => 1,
        ]);

        // Intentar registrar con mismo DNI
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombre' => 'Segundo',
            'apellido' => 'Cliente',
            'DNI' => '11222333', // Duplicado
            'calle' => 'Test',
            'altura' => '100',
            'codigoPostal' => '1000',
            'provincia_id' => $this->provincia->provinciaID,
            'localidad_id' => $this->localidad->localidadID,
            'tipo_cliente_id' => $this->tipoMinorista->tipoClienteID,
            'estado_cliente_id' => 1,
        ]);

        $response->assertSessionHasErrors('DNI');
    }

    // ─── CU-04: DAR DE BAJA CLIENTE ────────────────────────────

    /** @test */
    public function puede_dar_de_baja_cliente_sin_operaciones_pendientes()
    {
        $cliente = Cliente::create([
            'nombre' => 'Para',
            'apellido' => 'Baja',
            'DNI' => '40555777',
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'estadoClienteID' => 1,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('clientes.darDeBaja', $cliente->clienteID),
            ['motivo' => 'Cierre de cuenta por solicitud del cliente']
        );

        $response->assertSessionDoesntHaveErrors();

        $cliente->refresh();
        $this->assertEquals(2, $cliente->estadoClienteID); // Inactivo
    }

    /** @test */
    public function baja_mayorista_cierra_cuenta_corriente()
    {
        $cc = CuentaCorriente::create([
            'saldo' => 0,
            'limiteCredito' => 100000,
            'diasGracia' => 15,
            'estadoCuentaCorrienteID' => 1,
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Empresa',
            'apellido' => 'ACerrar',
            'DNI' => '50666888',
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'estadoClienteID' => 1,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('clientes.darDeBaja', $cliente->clienteID),
            ['motivo' => 'Cierre por inactividad']
        );

        $response->assertSessionDoesntHaveErrors();

        $cc->refresh();
        $this->assertEquals(4, $cc->estadoCuentaCorrienteID); // Cerrada
    }
}
