<?php

namespace Tests\Feature\Auditorias;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        Auditoria::create(['empresa_id' => 2, 'accion' => 'AJENO', 'motivo' => 'empresa 2']);

        // El admin de empresa 1 filtra por la acción de la empresa 2: no debe verla.
        $this->actingAs($admin)
            ->get(route('auditorias.index', ['accion' => 'AJENO']))
            ->assertInertia(fn (Assert $page) => $page->has('auditorias.data', 0));
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
        $this->assertJson(
            DB::table('auditorias')->where('auditoriaID', $registro->auditoriaID)->value('datos_nuevos')
        );
    }

    /** @test */
    public function los_campos_sensibles_no_se_guardan_en_texto_plano(): void
    {
        $registro = Auditoria::create([
            'accion' => 'CREAR_REPARACION',
            'tabla_afectada' => 'reparaciones',
            'datos_nuevos' => [
                'modelo_id' => 7,
                'clave_bloqueo' => '1234',   // sensible: se debe enmascarar
                'observaciones' => 'pantalla rota', // normal: intacto
            ],
        ]);

        $fresco = Auditoria::find($registro->auditoriaID);

        $this->assertSame('******', $fresco->datos_nuevos['clave_bloqueo']);
        $this->assertSame('pantalla rota', $fresco->datos_nuevos['observaciones']);
        $this->assertSame(7, $fresco->datos_nuevos['modelo_id']);

        // El crudo en la BD tampoco contiene el valor original.
        $crudo = DB::table('auditorias')->where('auditoriaID', $registro->auditoriaID)->value('datos_nuevos');
        $this->assertStringNotContainsString('1234', $crudo);
    }

    /** @test */
    public function un_campo_sensible_nulo_se_conserva_como_nulo(): void
    {
        $registro = Auditoria::create([
            'accion' => 'CREAR_REPARACION',
            'datos_nuevos' => ['clave_bloqueo' => null],
        ]);

        $this->assertNull(Auditoria::find($registro->auditoriaID)->datos_nuevos['clave_bloqueo']);
    }

    /** @test */
    public function consultar_el_log_se_audita_a_si_mismo(): void
    {
        $admin = $this->usuarioCon('administrador', 1);

        $this->actingAs($admin)->get(route('auditorias.index'))->assertOk();

        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_CONSULTAR_AUDITORIA,
            'usuarioID' => $admin->id,
            'tabla_afectada' => 'auditorias',
        ]);
    }

    /** @test */
    public function exportar_csv_funciona_y_se_audita(): void
    {
        $admin = $this->usuarioCon('administrador', 1);
        Auditoria::create(['accion' => 'ALGO', 'motivo' => 'para exportar']);

        $this->actingAs($admin)
            ->get(route('auditorias.exportar', ['formato' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_EXPORTAR_AUDITORIA,
            'usuarioID' => $admin->id,
        ]);
    }

    /** @test */
    public function exportar_pdf_responde_ok(): void
    {
        $admin = $this->usuarioCon('administrador', 1);
        Auditoria::create(['accion' => 'ALGO', 'motivo' => 'para exportar']);

        $this->actingAs($admin)
            ->get(route('auditorias.exportar', ['formato' => 'pdf']))
            ->assertOk();
    }

    /** @test */
    public function los_registros_son_inmutables_no_se_pueden_modificar(): void
    {
        $registro = Auditoria::create(['accion' => 'ALGO', 'motivo' => 'original']);

        $this->expectException(\RuntimeException::class);
        $registro->update(['motivo' => 'alterado']);
    }

    /** @test */
    public function los_registros_son_inmutables_no_se_pueden_eliminar(): void
    {
        $registro = Auditoria::create(['accion' => 'ALGO', 'motivo' => 'original']);

        $this->expectException(\RuntimeException::class);
        $registro->delete();
    }

    /** @test */
    public function la_purga_elimina_registros_antiguos_y_conserva_recientes(): void
    {
        $viejo = Auditoria::create(['accion' => 'VIEJO']);
        DB::table('auditorias')->where('auditoriaID', $viejo->auditoriaID)
            ->update(['created_at' => now()->subMonths(30)]);

        $reciente = Auditoria::create(['accion' => 'RECIENTE']);

        $this->artisan('auditoria:purgar', ['--meses' => 24])->assertExitCode(0);

        $this->assertDatabaseMissing('auditorias', ['auditoriaID' => $viejo->auditoriaID]);
        $this->assertDatabaseHas('auditorias', ['auditoriaID' => $reciente->auditoriaID]);
        $this->assertDatabaseHas('auditorias', ['accion' => Auditoria::ACCION_PURGAR_AUDITORIA]);
    }

    /** @test */
    public function la_purga_en_dry_run_no_elimina_nada(): void
    {
        $viejo = Auditoria::create(['accion' => 'VIEJO']);
        DB::table('auditorias')->where('auditoriaID', $viejo->auditoriaID)
            ->update(['created_at' => now()->subMonths(30)]);

        $this->artisan('auditoria:purgar', ['--meses' => 24, '--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('auditorias', ['auditoriaID' => $viejo->auditoriaID]);
    }
}
