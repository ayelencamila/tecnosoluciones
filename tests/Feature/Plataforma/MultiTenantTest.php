<?php

namespace Tests\Feature\Plataforma;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Rol;
use App\Models\User;
use App\Services\Plataforma\AltaEmpresaService;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifica el aislamiento multi-tenant y el nivel de plataforma.
 *
 * Cubre las tres fugas reales que existian antes: `users` sin filtrar por
 * empresa, roles de otra empresa asignables, y `empresas.activa` que no se
 * verificaba en ningun lado.
 */
class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // TenantContext guarda estado en propiedades estaticas, que sobreviven
        // entre tests dentro del mismo proceso de PHPUnit.
        TenantContext::limpiar();

        parent::tearDown();
    }

    /**
     * Crea una empresa con su rol administrador y su usuario administrador.
     */
    private function empresaConAdmin(string $nombre): array
    {
        $empresa = Empresa::create([
            'nombre' => $nombre,
            'slug' => \Illuminate\Support\Str::slug($nombre).'-'.uniqid(),
            'activa' => true,
        ]);

        $rol = Rol::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'administrador',
            'es_admin_empresa' => true,
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);

        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'rol_id' => $rol->rol_id,
        ]);

        return [$empresa, $rol, $admin];
    }

    /**
     * Crea un cliente en la empresa indicada.
     *
     * `tipos_cliente` es un catalogo POR EMPRESA, asi que el tipo se crea
     * dentro del mismo tenant que el cliente. Si se creara afuera quedaria en
     * la empresa default y la FK compuesta
     * (clientes.empresa_id, tipoClienteID) -> (tipos_cliente.empresa_id, tipoClienteID)
     * rechazaria el insert, que es justamente lo que debe hacer.
     *
     * `estados_cliente`, en cambio, es global y se crea una sola vez.
     */
    private function clienteEn(int $empresaId, string $dni): Cliente
    {
        $estado = \App\Models\EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Normal'],
            ['descripcion' => 'Al día']
        );

        return TenantContext::paraEmpresa($empresaId, function () use ($dni, $estado) {
            $tipo = \App\Models\TipoCliente::firstOrCreate(
                ['nombreTipo' => 'Minorista'],
                ['descripcion' => 'Público general', 'activo' => true]
            );

            return Cliente::create([
                'nombre' => 'Cliente',
                'apellido' => 'Test',
                'DNI' => $dni,
                'tipoClienteID' => $tipo->tipoClienteID,
                'estadoClienteID' => $estado->estadoClienteID ?? $estado->getKey(),
            ]);
        });
    }

    private function superUsuario(): User
    {
        $super = new User();
        $super->empresa_id = null;
        $super->rol_id = null;
        $super->es_super_admin = true;
        $super->name = 'Operador';
        $super->email = 'operador-'.uniqid().'@plataforma.test';
        $super->password = bcrypt('secreto');
        $super->activo = true;
        $super->save();

        return $super;
    }

    /** @test */
    public function un_admin_no_ve_usuarios_de_otra_empresa(): void
    {
        [, , $adminA] = $this->empresaConAdmin('Empresa A');
        [, , $adminB] = $this->empresaConAdmin('Empresa B');

        $this->actingAs($adminA)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('usuarios.data', 1));

        // El usuario de la otra empresa no es alcanzable ni por URL directa.
        $this->actingAs($adminA)
            ->get(route('admin.usuarios.show', $adminB->id))
            ->assertNotFound();
    }

    /** @test */
    public function un_admin_no_puede_modificar_un_usuario_de_otra_empresa(): void
    {
        [, , $adminA] = $this->empresaConAdmin('Empresa A');
        [, , $adminB] = $this->empresaConAdmin('Empresa B');

        $this->actingAs($adminA)
            ->post(route('admin.usuarios.reset-password', $adminB->id), [
                'generar_temporal' => true,
            ])
            ->assertNotFound();

        $this->actingAs($adminA)
            ->patch(route('admin.usuarios.toggle-activo', $adminB->id))
            ->assertNotFound();
    }

    /** @test */
    public function no_se_puede_asignar_a_un_usuario_un_rol_de_otra_empresa(): void
    {
        [, , $adminA] = $this->empresaConAdmin('Empresa A');
        [, $rolB] = $this->empresaConAdmin('Empresa B');

        $this->actingAs($adminA)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Intruso',
                'email' => 'intruso@test.com',
                'rol_id' => $rolB->rol_id,
                'password' => 'Str0ng!Pass1',
                'password_confirmation' => 'Str0ng!Pass1',
            ])
            ->assertSessionHasErrors('rol_id');

        $this->assertDatabaseMissing('users', ['email' => 'intruso@test.com']);
    }

    /** @test */
    public function los_datos_de_negocio_quedan_aislados_por_empresa(): void
    {
        [$empresaA, , $adminA] = $this->empresaConAdmin('Empresa A');
        [$empresaB, , $adminB] = $this->empresaConAdmin('Empresa B');

        $this->clienteEn($empresaA->id, '11111111');

        $this->actingAs($adminA);
        $this->assertSame(1, Cliente::count());

        $this->actingAs($adminB);
        $this->assertSame(0, Cliente::count(), 'La empresa B no debe ver clientes de la empresa A');
    }

    /**
     * Las tablas hijas no tienen `empresa_id`: heredan el tenant de su padre.
     * Una consulta que ARRANCA en ellas no pasa por el padre, asi que sin el
     * scope por relacion quedaba sin filtrar. Es el caso que hacia que el
     * dashboard contara el stock critico de todas las empresas juntas.
     *
     * @test
     */
    public function las_tablas_hijas_tambien_quedan_aisladas(): void
    {
        [$empresaA, , $adminA] = $this->empresaConAdmin('Empresa A');
        [, , $adminB] = $this->empresaConAdmin('Empresa B');

        // `modelos` no tiene empresa_id: cuelga de `marcas`, que si lo tiene.
        TenantContext::paraEmpresa($empresaA->id, function () {
            $marca = Marca::create(['nombre' => 'Marca de A', 'activo' => true]);
            Modelo::create(['marca_id' => $marca->id, 'nombre' => 'Modelo de A', 'activo' => true]);
        });

        $this->actingAs($adminA);
        $this->assertSame(1, Marca::count());
        $this->assertSame(1, Modelo::count(), 'La empresa A debe ver su modelo');

        $this->actingAs($adminB);
        $this->assertSame(0, Marca::count());
        $this->assertSame(0, Modelo::count(), 'La empresa B no debe ver modelos de la empresa A');

        // El dashboard de una empresa sin datos consulta tablas hijas (stock):
        // antes del scope por relacion rompia al leer el producto de otra empresa.
        $this->actingAs($adminB)->get(route('dashboard'))->assertOk();
    }

    /** @test */
    public function una_empresa_suspendida_pierde_el_acceso(): void
    {
        [$empresa, , $admin] = $this->empresaConAdmin('Empresa Morosa');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $empresa->update(['activa' => false]);

        // El corte es inmediato, incluso con la sesión ya abierta.
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @test */
    public function el_super_usuario_no_tiene_empresa_ni_rol(): void
    {
        $super = $this->superUsuario();

        $this->assertNull($super->empresa_id);
        $this->assertNull($super->rol_id);
        $this->assertTrue($super->esSuperAdmin());
        $this->assertFalse($super->esAdminEmpresa());
        // Pasa cualquier permiso aunque no tenga rol del cual heredarlos.
        $this->assertTrue($super->hasPermission('ventas.anular'));
    }

    /** @test */
    public function la_base_rechaza_un_super_usuario_con_empresa(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'empresa_id' => 1,
            'rol_id' => null,
            'es_super_admin' => true,
            'name' => 'Incoherente',
            'email' => 'incoherente@test.com',
            'password' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function el_super_usuario_sin_empresa_elegida_va_al_panel_de_plataforma(): void
    {
        $super = $this->superUsuario();

        $this->actingAs($super)
            ->get(route('dashboard'))
            ->assertRedirect(route('super.empresas.index'));
    }

    /** @test */
    public function el_super_usuario_al_entrar_ve_solo_los_datos_de_esa_empresa(): void
    {
        [$empresaA] = $this->empresaConAdmin('Empresa A');
        [$empresaB] = $this->empresaConAdmin('Empresa B');

        $this->clienteEn($empresaA->id, '22222222');

        $super = $this->superUsuario();

        $this->actingAs($super)
            ->post(route('super.empresas.entrar', $empresaA->id))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($empresaA->id, TenantContext::empresaId());
        $this->assertSame(1, Cliente::count());

        $this->actingAs($super)->post(route('super.empresas.entrar', $empresaB->id));
        $this->assertSame(0, Cliente::count(), 'Dentro de la empresa B no debe ver clientes de la A');
    }

    /** @test */
    public function el_panel_de_plataforma_es_invisible_para_un_usuario_de_empresa(): void
    {
        [, , $admin] = $this->empresaConAdmin('Empresa A');

        // 404 y no 403: al cliente no se le revela que existe un nivel superior.
        $this->actingAs($admin)->get(route('super.empresas.index'))->assertNotFound();
    }

    /** @test */
    public function el_alta_de_empresa_crea_rol_admin_y_usuario_en_una_sola_operacion(): void
    {
        $empresa = app(AltaEmpresaService::class)->ejecutar(
            ['nombre' => 'Cliente Nuevo', 'cuit' => '30-11112222-3'],
            ['name' => 'Dueño', 'email' => 'dueno@clientenuevo.test', 'password' => 'Str0ng!Pass1'],
        );

        $this->assertTrue($empresa->activa);

        $rol = Rol::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
        $this->assertNotNull($rol, 'La empresa nueva debe tener su rol administrador');
        $this->assertTrue($rol->es_admin_empresa);
        $this->assertNotEmpty($rol->permisos);

        $usuario = User::where('empresa_id', $empresa->id)->first();
        $this->assertNotNull($usuario, 'La empresa nueva debe tener su usuario administrador');
        $this->assertSame($rol->rol_id, $usuario->rol_id);
        $this->assertTrue($usuario->esAdminEmpresa());

        // Y arranca con sus propios parámetros de operación, no los de otra empresa.
        $configs = Configuracion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();
        $this->assertGreaterThan(0, $configs);
    }

    /** @test */
    public function los_parametros_de_configuracion_son_independientes_por_empresa(): void
    {
        [$empresaA, , $adminA] = $this->empresaConAdmin('Empresa A');
        [$empresaB, , $adminB] = $this->empresaConAdmin('Empresa B');

        TenantContext::paraEmpresa($empresaA->id, fn () => Configuracion::set('stock_minimo_global', 5));
        TenantContext::paraEmpresa($empresaB->id, fn () => Configuracion::set('stock_minimo_global', 99));

        // Sin limpiar caché entre lecturas: la clave de caché incluye la empresa.
        $this->actingAs($adminA);
        $this->assertEquals(5, Configuracion::get('stock_minimo_global'));

        $this->actingAs($adminB);
        $this->assertEquals(99, Configuracion::get('stock_minimo_global'));

        $this->actingAs($adminA);
        $this->assertEquals(5, Configuracion::get('stock_minimo_global'));
    }
}
