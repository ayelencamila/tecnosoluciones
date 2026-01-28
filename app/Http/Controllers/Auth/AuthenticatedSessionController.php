<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Validar credenciales y reglas de negocio (Activo/Bloqueado)
        // La auditoría del login se registra en LoginRequest
        $request->authenticate();

        // 2. Regenerar sesión
        $request->session()->regenerate();

        // 3. Redirección INTELIGENTE
        // Si es Técnico (rol_id 3) -> va a su lista de trabajo.
        // Si es Admin (1) o Vendedor (2) -> van al Dashboard.
        if ($request->user()->rol_id === 3) {
            return redirect()->intended(route('reparaciones.index'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     * CU: Cerrar Sesión - Registra en auditoría
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Registrar cierre de sesión en auditoría
        if ($user) {
            Auditoria::registrar(
                Auditoria::ACCION_LOGOUT,
                'users',
                $user->id,
                null,
                [
                    'ip' => $request->ip(),
                    'motivo' => 'cierre_explicito',
                ],
                'Cierre de sesión explícito'
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
