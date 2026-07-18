<?php

namespace Tests\Feature\Auditorias;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCon(string $rolNombre, int $empresaId = 1): User
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => $rolNombre, 'empresa_id' => $empresaId],
            ['descripcion' => $rolNombre, 'permisos' => [], 'activo' => true],
        );

        return User::factory()->create([
            'rol_id' => $rol->rol_id,
            'empresa_id' => $empresaId,
        ]);
    }

    /** @test */
    public function el_administrador_puede_ver_el_log_de_auditoria(): void
    {
        $admin = $this->usuarioCon('administrador');

        $this->actingAs($admin)
            ->get(route('auditorias.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auditorias/Index'));
    }

    /** @test */
    public function un_usuario_no_administrador_no_puede_acceder(): void
    {
        $vendedor = $this->usuarioCon('vendedor');

        $this->actingAs($vendedor)
            ->get(route('auditorias.index'))
            ->assertForbidden();
    }

    /** @test */
    public function el_log_esta_scopeado_por_empresa(): void
    {
        $admin = $this->usuarioCon('administrador', 1);
        Empresa::create(['nombre' => 'Otra Empresa', 'slug' => 'otra-empresa']);

        // Un registro de cada empresa.
        Auditoria::create(['empresa_id' => 1, 'accion' => 'PROPIO', 'motivo' => 'empresa 1']);
        Auditoria::create(['empresa_id' => 2, 'accion' => 'AJENO', 'motivo' => 'empresa 2']);

        $this->actingAs($admin)
            ->get(route('auditorias.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('auditorias.data', 1)
                ->where('auditorias.data.0.accion', 'PROPIO')
            );
    }

    /** @test */
    public function el_empresa_id_se_completa_automaticamente_al_crear(): void
    {
        $admin = $this->usuarioCon('administrador', 1);
        $this->actingAs($admin);

        $registro = Auditoria::create(['accion' => 'PRUEBA', 'motivo' => 'sin empresa_id explícito']);

        $this->assertSame(1, $registro->empresa_id);
    }

    /** @test */
    public function los_datos_se_guardan_como_json_sin_doble_codificar(): void
    {
        $admin = $this->usuarioCon('administrador', 1);
        $this->actingAs($admin);

        $registro = Auditoria::create([
            'accion' => 'MODIFICAR_PRODUCTO',
            'tabla_afectada' => 'productos',
            'registro_id' => 5,
            'datos_anteriores' => ['nombre' => 'Antes'],
            'datos_nuevos' => ['nombre' => 'Después'],
        ]);

        $fresco = Auditoria::find($registro->auditoriaID);

        $this->assertIsArray($fresco->datos_nuevos);
        $this->assertSame('Después', $fresco->datos_nuevos['nombre']);
        // El valor crudo en la BD debe ser JSON limpio, no una cadena escapada.
        $this->assertJson(
            \DB::table('auditorias')->where('auditoriaID', $registro->auditoriaID)->value('datos_nuevos')
        );
    }
}
