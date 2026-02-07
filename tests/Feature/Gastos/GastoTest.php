<?php

namespace Tests\Feature\Gastos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\Rol;
use App\Models\Gasto;
use App\Models\CategoriaGasto;

class GastoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CategoriaGasto $categoriaGasto;
    protected CategoriaGasto $categoriaPerdida;

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

        // Categorías
        $this->categoriaGasto = CategoriaGasto::create([
            'nombre' => 'Alquiler',
            'descripcion' => 'Alquiler del local',
            'tipo' => 'gasto',
            'activo' => true,
        ]);
        $this->categoriaPerdida = CategoriaGasto::create([
            'nombre' => 'Robo',
            'descripcion' => 'Pérdida por robo',
            'tipo' => 'perdida',
            'activo' => true,
        ]);
    }

    // ─── CU-35: REGISTRAR GASTO ─────────────────────────────────

    /** @test */
    public function puede_registrar_gasto()
    {
        $response = $this->actingAs($this->admin)->post(route('gastos.store'), [
            'categoria_gasto_id' => $this->categoriaGasto->categoria_gasto_id,
            'fecha' => '2025-01-15',
            'descripcion' => 'Pago mensual alquiler enero',
            'monto' => 150000,
            'comprobante' => 'FAC-001',
            'observaciones' => 'Recibo firmado',
        ]);

        $response->assertRedirect(route('gastos.index'));
        $response->assertSessionHas('success');

        $gasto = Gasto::first();
        $this->assertNotNull($gasto);
        $this->assertEquals(150000, (float) $gasto->monto);
        $this->assertEquals('Pago mensual alquiler enero', $gasto->descripcion);
        $this->assertEquals($this->admin->id, $gasto->usuario_id);
        $this->assertFalse($gasto->anulado);
    }

    /** @test */
    public function puede_registrar_perdida()
    {
        $response = $this->actingAs($this->admin)->post(route('gastos.store'), [
            'categoria_gasto_id' => $this->categoriaPerdida->categoria_gasto_id,
            'fecha' => '2025-02-10',
            'descripcion' => 'Robo de mercadería',
            'monto' => 50000,
        ]);

        $response->assertRedirect(route('gastos.index'));

        $gasto = Gasto::first();
        $this->assertNotNull($gasto);
        $this->assertEquals('perdida', $gasto->tipo);
    }

    /** @test */
    public function validacion_requiere_campos_obligatorios()
    {
        $response = $this->actingAs($this->admin)->post(route('gastos.store'), []);

        $response->assertSessionHasErrors(['categoria_gasto_id', 'fecha', 'descripcion', 'monto']);
    }

    /** @test */
    public function no_permite_monto_cero()
    {
        $response = $this->actingAs($this->admin)->post(route('gastos.store'), [
            'categoria_gasto_id' => $this->categoriaGasto->categoria_gasto_id,
            'fecha' => '2025-01-15',
            'descripcion' => 'Test',
            'monto' => 0,
        ]);

        $response->assertSessionHasErrors('monto');
    }

    // ─── ANULACIÓN ──────────────────────────────────────────────

    /** @test */
    public function puede_anular_gasto()
    {
        $gasto = Gasto::create([
            'categoria_gasto_id' => $this->categoriaGasto->categoria_gasto_id,
            'fecha' => '2025-01-15',
            'descripcion' => 'Para anular',
            'monto' => 10000,
            'usuario_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('gastos.anular', $gasto->gasto_id)
        );

        $response->assertSessionHas('success');

        $gasto->refresh();
        $this->assertTrue($gasto->anulado);
    }

    /** @test */
    public function no_puede_anular_gasto_ya_anulado()
    {
        $gasto = Gasto::create([
            'categoria_gasto_id' => $this->categoriaGasto->categoria_gasto_id,
            'fecha' => '2025-01-15',
            'descripcion' => 'Ya anulado',
            'monto' => 5000,
            'usuario_id' => $this->admin->id,
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('gastos.anular', $gasto->gasto_id)
        );

        $response->assertSessionHas('error');
    }
}
