<?php

namespace Tests\Feature\Privacidad;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\Empresa;
use App\Models\EstadoCliente;
use App\Models\Localidad;
use App\Models\Proveedor;
use App\Models\Provincia;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CLAUDE.md, Auditoría Legal punto 4: correos, teléfonos y direcciones no
 * deben quedar expuestos a usuarios sin el rol adecuado.
 *
 * El criterio implementado: los listados recorribles entregan el contacto
 * enmascarado salvo que el rol tenga `*.ver_datos_contacto`, con una
 * excepción deliberada para quien puede editar, porque un formulario cargado
 * con el dato enmascarado lo guardaría así y destruiría el original.
 */
class DatosSensiblesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'nombre' => 'Privacidad SA',
            'slug' => 'privacidad-'.uniqid(),
            'activa' => true,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::limpiar();

        parent::tearDown();
    }

    /** Crea un usuario con exactamente los permisos indicados. */
    private function usuarioCon(array $permisos): User
    {
        $rol = Rol::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'operativo-'.Str::random(6),
            'es_admin_empresa' => false,
            'descripcion' => 'Rol de prueba',
            'permisos' => $permisos,
            'activo' => true,
        ]);

        return User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'rol_id' => $rol->rol_id,
        ]);
    }

    private function clienteDePrueba(): Cliente
    {
        // Los catálogos globales (estado, provincia, localidad) viven fuera
        // del tenant; el tipo de cliente es por empresa y va adentro.
        $estado = EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Activo'],
            ['descripcion' => 'Al día']
        );

        $provincia = Provincia::firstOrCreate(['nombre' => 'Misiones']);
        $localidad = Localidad::firstOrCreate(
            ['nombre' => 'Posadas', 'provinciaID' => $provincia->provinciaID],
        );

        return TenantContext::paraEmpresa($this->empresa->id, function () use ($estado, $localidad) {
            $tipo = TipoCliente::create(['nombreTipo' => 'Minorista', 'activo' => true]);

            $direccion = Direccion::create([
                'calle' => 'Avenida Siempreviva',
                'altura' => '742',
                'localidadID' => $localidad->localidadID,
            ]);

            return Cliente::create([
                'nombre' => 'Juana',
                'apellido' => 'Pereyra',
                'DNI' => '30123456',
                'mail' => 'juana.pereyra@gmail.com',
                'whatsapp' => '3764123456',
                'telefono' => '3764987654',
                'tipoClienteID' => $tipo->tipoClienteID,
                'estadoClienteID' => $estado->estadoClienteID,
                'direccionID' => $direccion->direccionID,
            ]);
        });
    }

    private function proveedorDePrueba(): Proveedor
    {
        return TenantContext::paraEmpresa($this->empresa->id, fn () => Proveedor::create([
            'razon_social' => 'Repuestos del Litoral SRL',
            'cuit' => '30712345678',
            'email' => 'ventas@litoral.com.ar',
            'telefono' => '3764555000',
            'whatsapp' => '3764555001',
            'activo' => true,
        ]));
    }

    /** @test */
    public function un_rol_de_solo_lectura_recibe_el_contacto_del_cliente_enmascarado(): void
    {
        $cliente = $this->clienteDePrueba();
        $usuario = $this->usuarioCon(['clientes.ver']);

        $respuesta = $this->actingAs($usuario)->get(route('clientes.index'));

        $respuesta->assertOk();

        $fila = $respuesta->viewData('page')['props']['clientes']['data'][0];

        $this->assertSame('j•••@gmail.com', $fila['mail']);
        $this->assertSame('••••3456', $fila['whatsapp']);
        $this->assertSame('••••7654', $fila['telefono']);
        $this->assertSame('••••456', $fila['DNI']);
        $this->assertTrue($fila['datos_contacto_parciales']);

        // El domicilio exacto se oculta; la zona queda, porque el listado
        // filtra por provincia y los reportes agrupan por localidad.
        $this->assertSame('••••••', $fila['direccion']['calle']);
        $this->assertSame('••••••', $fila['direccion']['altura']);
        $this->assertNotNull($fila['direccion']['localidad']['nombre']);

        // Lo esencial: el dato real no viajó en la respuesta de ninguna forma.
        $respuesta->assertDontSee('juana.pereyra@gmail.com');
        $respuesta->assertDontSee('3764123456');
        $respuesta->assertDontSee('30123456');
        $respuesta->assertDontSee('Avenida Siempreviva');

        $this->assertSame('juana.pereyra@gmail.com', $cliente->fresh()->mail, 'El dato guardado no se toca');
    }

    /** @test */
    public function el_permiso_explicito_habilita_el_contacto_completo(): void
    {
        $this->clienteDePrueba();
        $usuario = $this->usuarioCon(['clientes.ver', 'clientes.ver_datos_contacto']);

        $respuesta = $this->actingAs($usuario)->get(route('clientes.index'));

        $fila = $respuesta->viewData('page')['props']['clientes']['data'][0];

        $this->assertSame('juana.pereyra@gmail.com', $fila['mail']);
        $this->assertSame('3764123456', $fila['whatsapp']);
        $this->assertSame('30123456', $fila['DNI']);
        $this->assertSame('Avenida Siempreviva', $fila['direccion']['calle']);
        $this->assertFalse($fila['datos_contacto_parciales']);
    }

    /**
     * Si el formulario de edición recibiera "j•••@gmail.com", al guardar
     * escribiría esa cadena en la base. Por eso editar implica ver.
     *
     * @test
     */
    public function quien_puede_editar_recibe_el_dato_real_para_no_corromperlo(): void
    {
        $cliente = $this->clienteDePrueba();
        $usuario = $this->usuarioCon(['clientes.ver', 'clientes.editar']);

        $this->assertTrue($usuario->puedeVerDatosContacto('clientes'));

        $respuesta = $this->actingAs($usuario)->get(route('clientes.edit', $cliente->clienteID));

        $respuesta->assertOk();
        $respuesta->assertSee('juana.pereyra@gmail.com', false);
    }

    /** @test */
    public function el_administrador_de_empresa_ve_todo_sin_permisos_explicitos(): void
    {
        $this->clienteDePrueba();

        $rolAdmin = Rol::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'administrador',
            'es_admin_empresa' => true,
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);

        $admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'rol_id' => $rolAdmin->rol_id,
        ]);

        $fila = $this->actingAs($admin)
            ->get(route('clientes.index'))
            ->viewData('page')['props']['clientes']['data'][0];

        $this->assertSame('juana.pereyra@gmail.com', $fila['mail']);
        $this->assertFalse($fila['datos_contacto_parciales']);
    }

    /** @test */
    public function el_buscador_asincrono_tambien_enmascara_el_documento(): void
    {
        $this->clienteDePrueba();
        $usuario = $this->usuarioCon(['clientes.ver']);

        $respuesta = $this->actingAs($usuario)->getJson('/api/clientes/buscar?q=Pereyra');

        $respuesta->assertOk();
        // Se conserva el arreglo plano que espera el autocomplete.
        $respuesta->assertJsonCount(1);
        $respuesta->assertJsonPath('0.DNI', '••••456');
        $respuesta->assertDontSee('30123456');
    }

    /** @test */
    public function el_listado_de_reparaciones_enmascara_el_telefono_del_cliente(): void
    {
        $usuario = $this->usuarioCon(['reparaciones.ver', 'clientes.ver']);

        $respuesta = $this->actingAs($usuario)->get(route('reparaciones.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('3764123456');
    }

    /** @test */
    public function un_rol_de_solo_lectura_recibe_el_contacto_del_proveedor_enmascarado(): void
    {
        $this->proveedorDePrueba();
        $usuario = $this->usuarioCon(['proveedores.ver']);

        $respuesta = $this->actingAs($usuario)->get(route('proveedores.index'));

        $respuesta->assertOk();

        $fila = $respuesta->viewData('page')['props']['proveedores']['data'][0];

        $this->assertSame('v•••@litoral.com.ar', $fila['email']);
        $this->assertSame('••••5000', $fila['telefono']);
        $this->assertSame('••••678', $fila['cuit']);
        $this->assertSame('Repuestos del Litoral SRL', $fila['razon_social'], 'La razón social es dato de trabajo');

        $respuesta->assertDontSee('ventas@litoral.com.ar');
        $respuesta->assertDontSee('30712345678');
    }

    /**
     * El resource de proveedores no tenía ningún `permiso:`, así que los
     * permisos existían en el config pero nunca se verificaban.
     *
     * @test
     */
    public function el_modulo_de_proveedores_ahora_exige_permiso(): void
    {
        $proveedor = $this->proveedorDePrueba();

        // Un rol sin ningún permiso de proveedores no entra.
        $sinPermiso = $this->usuarioCon(['clientes.ver']);
        $this->actingAs($sinPermiso)->get(route('proveedores.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->get(route('proveedores.show', $proveedor->id))->assertForbidden();

        // Ver no habilita escribir.
        $soloLectura = $this->usuarioCon(['proveedores.ver']);
        $this->actingAs($soloLectura)->get(route('proveedores.index'))->assertOk();
        $this->actingAs($soloLectura)->get(route('proveedores.create'))->assertForbidden();
        $this->actingAs($soloLectura)->delete(route('proveedores.destroy', $proveedor->id))->assertForbidden();
        $this->actingAs($soloLectura)
            ->patch(route('proveedores.actualizar-calificacion', $proveedor->id), ['calificacion' => 1])
            ->assertForbidden();

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }

    /**
     * Un dato más corto que la parte visible se oculta entero: mostrar "•4"
     * no protegería nada.
     *
     * @test
     */
    public function un_dato_demasiado_corto_se_oculta_por_completo(): void
    {
        $this->assertSame('••••••', \App\Support\DatoSensible::telefono('123'));
        $this->assertSame('••••••', \App\Support\DatoSensible::documento('12'));
        $this->assertSame('••••••', \App\Support\DatoSensible::email('sin-arroba'));

        // Un valor vacío se devuelve tal cual: no se inventa un dato oculto
        // donde no hay dato, porque la vista mostraría "••••••" en lugar de "-".
        $this->assertNull(\App\Support\DatoSensible::telefono(null));
        $this->assertSame('', \App\Support\DatoSensible::email(''));
    }
}
