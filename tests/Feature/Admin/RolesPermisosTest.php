<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un usuario con un rol de permisos dados (empresa 1 por defecto).
     */
    private function userConPermisos(array $permisos, string $nombreRol, int $empresa = 1): User
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => $nombreRol, 'empresa_id' => $empresa],
            ['descripcion' => $nombreRol, 'activo' => true],
        );
        $rol->update(['permisos' => $permisos]);

        return User::factory()->create(['rol_id' => $rol->rol_id, 'empresa_id' => $empresa]);
    }

    /** @test */
    public function con_el_permiso_ver_accede_al_listado_de_reparaciones()
    {
        $user = $this->userConPermisos(['reparaciones.ver'], 'solo_ver');

        $this->actingAs($user)->get(route('reparaciones.index'))->assertOk();
    }

    /** @test */
    public function sin_el_permiso_no_accede_al_listado_de_reparaciones()
    {
        $user = $this->userConPermisos(['dashboard.ver'], 'sin_reparaciones');

        $this->actingAs($user)->get(route('reparaciones.index'))->assertForbidden();
    }

    /** @test */
    public function crear_reparacion_requiere_el_permiso_crear()
    {
        // Tiene ver pero NO crear → 403 en el formulario de alta.
        $soloVer = $this->userConPermisos(['reparaciones.ver'], 'solo_ver_2');
        $this->actingAs($soloVer)->get(route('reparaciones.create'))->assertForbidden();

        // Con crear → accede.
        $conCrear = $this->userConPermisos(['reparaciones.ver', 'reparaciones.crear'], 'con_crear');
        $this->actingAs($conCrear)->get(route('reparaciones.create'))->assertOk();
    }

    /** @test */
    public function el_administrador_pasa_sin_permisos_explicitos()
    {
        // Rol administrador SIN permisos en el array: igual debe pasar (bypass).
        $admin = $this->userConPermisos([], 'administrador');

        $this->actingAs($admin)->get(route('reparaciones.index'))->assertOk();
        $this->actingAs($admin)->get(route('reparaciones.create'))->assertOk();
    }

    /** @test */
    public function clientes_requiere_permiso_ver()
    {
        $sin = $this->userConPermisos(['dashboard.ver'], 'sin_clientes');
        $this->actingAs($sin)->get(route('clientes.index'))->assertForbidden();

        $con = $this->userConPermisos(['clientes.ver'], 'con_clientes');
        $this->actingAs($con)->get(route('clientes.index'))->assertOk();
    }

    /** @test */
    public function productos_requiere_permiso_ver()
    {
        $sin = $this->userConPermisos(['dashboard.ver'], 'sin_productos');
        $this->actingAs($sin)->get(route('productos.index'))->assertForbidden();

        $con = $this->userConPermisos(['productos.ver'], 'con_productos');
        $this->actingAs($con)->get(route('productos.index'))->assertOk();
    }

    /** @test */
    public function crear_producto_requiere_permiso_crear()
    {
        // Perfil tipo vendedor: ve productos pero (en este caso) no tiene crear.
        $soloVer = $this->userConPermisos(['productos.ver'], 'prod_solo_ver');
        $this->actingAs($soloVer)->get(route('productos.create'))->assertForbidden();

        $conCrear = $this->userConPermisos(['productos.ver', 'productos.crear'], 'prod_con_crear');
        $this->actingAs($conCrear)->get(route('productos.create'))->assertOk();
    }

    /** @test */
    public function un_rol_de_otra_empresa_no_es_gestionable()
    {
        $admin = $this->userConPermisos([], 'administrador'); // empresa 1

        Empresa::create(['nombre' => 'Otra', 'slug' => 'otra-'.uniqid()]);
        $rolAjeno = Rol::create([
            'empresa_id' => 2,
            'nombre' => 'rol_empresa_2',
            'descripcion' => 'x',
            'permisos' => [],
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $rolAjeno->rol_id))
            ->assertNotFound();
    }
}
