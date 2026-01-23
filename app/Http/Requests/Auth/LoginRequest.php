<?php

namespace App\Http\Requests\Auth;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Request de autenticación con validaciones de seguridad mejoradas.
 * 
 * Implementa CU Iniciar Sesión:
 * - Validación de credenciales
 * - Verificación de cuenta activa
 * - Verificación de cuenta no bloqueada
 * - Bloqueo automático por intentos fallidos
 * - Registro en auditoría
 */
class LoginRequest extends FormRequest
{
    /**
     * Número máximo de intentos antes de bloquear
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Minutos de bloqueo después de exceder intentos
     */
    protected const LOCKOUT_MINUTES = 15;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Implementa las validaciones del CU Iniciar Sesión.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Buscar el usuario para validaciones previas
        $user = User::where('email', $this->email)->first();

        // Validar si el usuario existe y las credenciales son correctas
        if (!$user || !Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->handleFailedAttempt($user);
            
            throw ValidationException::withMessages([
                'email' => 'Nombre de usuario o contraseña incorrectos.',
            ]);
        }

        // Usuario encontrado y credenciales correctas, verificar estado
        
        // Excepción 6b: Cuenta inactiva
        if (!$user->estaActivo()) {
            Auth::logout();
            
            Auditoria::registrar(
                Auditoria::ACCION_ACCESO_DENEGADO,
                'users',
                $user->id,
                null,
                ['motivo' => 'cuenta_inactiva', 'ip' => $this->ip()],
                'Intento de acceso con cuenta inactiva'
            );

            throw ValidationException::withMessages([
                'email' => 'Su cuenta se encuentra inactiva. Contacte al administrador.',
            ]);
        }

        // Excepción 6c: Cuenta bloqueada
        if ($user->estaBloqueado()) {
            Auth::logout();
            
            Auditoria::registrar(
                Auditoria::ACCION_ACCESO_DENEGADO,
                'users',
                $user->id,
                null,
                ['motivo' => 'cuenta_bloqueada', 'ip' => $this->ip(), 'bloqueado_hasta' => $user->bloqueado_hasta],
                'Intento de acceso con cuenta bloqueada'
            );

            throw ValidationException::withMessages([
                'email' => 'Su cuenta se encuentra bloqueada. Contacte al administrador.',
            ]);
        }

        // Login exitoso - limpiar rate limiter y registrar en auditoría
        RateLimiter::clear($this->throttleKey());

        Auditoria::registrar(
            Auditoria::ACCION_LOGIN,
            'users',
            $user->id,
            null,
            [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'remember' => $this->boolean('remember'),
            ],
            'Inicio de sesión exitoso'
        );
    }

    /**
     * Maneja un intento de login fallido.
     * Incrementa contador y bloquea si excede el límite.
     */
    protected function handleFailedAttempt(?User $user): void
    {
        RateLimiter::hit($this->throttleKey(), self::LOCKOUT_MINUTES * 60);

        $attempts = RateLimiter::attempts($this->throttleKey());

        // Si el usuario existe y excedió los intentos, bloquearlo en BD
        if ($user && $attempts >= self::MAX_ATTEMPTS) {
            $user->bloqueado_hasta = now()->addMinutes(self::LOCKOUT_MINUTES);
            $user->save();

            Auditoria::registrar(
                Auditoria::ACCION_BLOQUEO_AUTOMATICO,
                'users',
                $user->id,
                null,
                [
                    'intentos_fallidos' => $attempts,
                    'ip' => $this->ip(),
                    'bloqueado_hasta' => $user->bloqueado_hasta,
                ],
                'Bloqueo automático por intentos fallidos de login'
            );
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Su cuenta ha sido bloqueada por demasiados intentos fallidos. Intente nuevamente en ' . ceil($seconds / 60) . ' minutos o contacte al administrador.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
