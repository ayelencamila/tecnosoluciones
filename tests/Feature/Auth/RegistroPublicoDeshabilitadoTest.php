<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * El registro público viene de Laravel Breeze y encaja mal con un sistema
 * multi-empresa: con la ruta abierta, cualquiera que conozca la URL se crea
 * una cuenta operativa dentro de una empresa real y pasa a ver sus clientes,
 * sus precios y sus ventas.
 *
 * El alta de usuarios corresponde al administrador de cada empresa, que es
 * quien sabe qué rol le toca a cada persona. Esta prueba fija ese estado
 * para que nadie lo reabra sin darse cuenta.
 */
class RegistroPublicoDeshabilitadoTest extends TestCase
{
    public function test_el_registro_publico_esta_apagado_por_defecto(): void
    {
        $this->assertFalse(
            config('auth.registro_publico'),
            'El registro público debe venir apagado: habilitarlo es una decisión explícita del .env'
        );
    }

    public function test_la_ruta_de_registro_no_existe(): void
    {
        $this->assertFalse(Route::has('register'), 'La ruta no debe estar registrada');

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'acepta_terminos' => true,
        ])->assertNotFound();

        $this->assertGuest();
    }

    /**
     * El resto del circuito de autenticación no se toca: iniciar sesión y
     * recuperar la contraseña siguen disponibles.
     */
    public function test_el_inicio_de_sesion_sigue_disponible(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }
}
