<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Crea el operador de la plataforma (super usuario).
 *
 * Deliberadamente NO existe una pantalla web para esto. El super usuario es
 * el privilegio maximo del sistema: puede entrar a los datos de todos los
 * clientes. Concederlo requiere acceso al servidor, no una sesion del
 * navegador, de modo que no haya ninguna ruta HTTP capaz de escalar
 * privilegios hasta este nivel. Es el mismo criterio que
 * `createsuperuser` en Django.
 */
class CrearSuperUsuario extends Command
{
    protected $signature = 'plataforma:crear-super-usuario
                            {--name= : Nombre del operador}
                            {--email= : Email de acceso}
                            {--password= : Contraseña (si se omite, se pide de forma interactiva)}';

    protected $description = 'Crea un super usuario de plataforma, con acceso a todas las empresas';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre del operador');
        $email = $this->option('email') ?: $this->ask('Email de acceso');
        $password = $this->option('password') ?: $this->secret('Contraseña');

        $validador = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
            ],
            [
                'email.unique' => 'Ese email ya está registrado en el sistema.',
            ]
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error("• {$error}");
            }

            return self::FAILURE;
        }

        // empresa_id y rol_id quedan en NULL: el operador de plataforma no
        // pertenece a ninguna empresa ni tiene rol, porque los roles son
        // entidad debil de una empresa. La restriccion CHECK de la tabla
        // `users` verifica esta coherencia a nivel de base de datos.
        $usuario = new User();
        $usuario->empresa_id = null;
        $usuario->rol_id = null;
        $usuario->es_super_admin = true;
        $usuario->name = $name;
        $usuario->email = $email;
        $usuario->password = Hash::make($password);
        $usuario->activo = true;
        $usuario->save();

        $this->newLine();
        $this->info("✅ Super usuario creado (#{$usuario->id}): {$usuario->email}");
        $this->line('   Al iniciar sesión vas a entrar al panel de plataforma para elegir empresa.');

        return self::SUCCESS;
    }
}
