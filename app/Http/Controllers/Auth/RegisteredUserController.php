<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Empresa a la que se asignan los usuarios que se registran solos.
     *
     * El registro publico es una herencia del scaffolding de Breeze y encaja
     * mal con un sistema multi-empresa: cualquiera puede crearse una cuenta
     * dentro de esta empresa. Lo correcto es que cada administrador de
     * empresa de de alta a su gente desde /admin/usuarios y que esta ruta se
     * deshabilite. Mientras siga activa, al menos queda explicito a que
     * empresa entra el usuario, en lugar de depender del DEFAULT de la
     * columna.
     */
    private const EMPRESA_REGISTRO_PUBLICO = 1;

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // CLAUDE.md (Auditoria Legal, punto 3): el flujo de registro debe
            // exigir la aceptacion de las reglas de uso. `accepted` rechaza
            // cualquier valor que no sea una aceptacion explicita.
            'acepta_terminos' => ['accepted'],
        ], [
            'acepta_terminos.accepted' => 'Debés aceptar los Términos y Condiciones y la Política de Privacidad para registrarte.',
        ]);

        // El rol se busca acotado a la empresa default de forma EXPLICITA.
        // En el registro no hay usuario autenticado, de modo que TenantContext
        // no resuelve ningun tenant y el Global Scope de `Rol` no restringe:
        // sin este filtro, `where('nombre','vendedor')->first()` podia devolver
        // el rol de otra empresa y crear un usuario incoherente (empresa 1 con
        // el rol de la empresa 3).
        $empresaId = self::EMPRESA_REGISTRO_PUBLICO;

        $defaultRol = Rol::where('empresa_id', $empresaId)
            ->where('nombre', 'vendedor')
            ->first()
            ?? Rol::where('empresa_id', $empresaId)->first();

        abort_if(! $defaultRol, 503, 'El registro no está disponible: no hay roles configurados.');

        $user = User::create([
            'empresa_id' => $empresaId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $defaultRol->rol_id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
