<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El rol se crea en la empresa 1, que es la que usa el registro público.
        Rol::firstOrCreate(
            ['nombre' => 'vendedor', 'empresa_id' => 1],
            ['descripcion' => 'Vendedor', 'activo' => true]
        );
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'acepta_terminos' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * CLAUDE.md (Auditoría Legal, punto 3): el registro debe exigir la
     * aceptación de las reglas de uso, así que sin ella no se crea la cuenta.
     */
    public function test_registration_requires_accepting_terms(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'sin-aceptar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('acepta_terminos');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sin-aceptar@example.com']);
    }

    public function test_legal_pages_are_publicly_reachable(): void
    {
        $this->get(route('legal.terminos'))->assertOk();
        $this->get(route('legal.privacidad'))->assertOk();
    }
}
