<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Crea la tabla `empresas` (entidad fuerte segun Elmasri Cap.7) que actua
 * como tenant raiz para el modelo multi-tenant.
 *
 * Estrategia Strangler Fig (Fowler): se inserta de inmediato una empresa
 * default con id=1 a la que se backfilleara toda la informacion existente
 * en migraciones siguientes. De esta manera, el sistema arranca multi-tenant
 * desde el dia 1 sin violar integridad de entidad (Elmasri) ni romper el
 * codigo en produccion.
 *
 * Ademas, esta migracion preserva los valores que hoy viven en la tabla
 * EAV `configuracion` (claves nombre_empresa, cuit_empresa, etc.) copiandolos
 * a las columnas tipadas de la nueva entidad fuerte. La tabla `configuracion`
 * no se modifica: la depreciacion progresiva se hara en Fase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('slug', 160)->unique()->comment('Identificador url-safe para subdominios o rutas');
            $table->string('logo', 255)->nullable()->comment('Path relativo en storage para el logo editable por la empresa');
            $table->string('cuit', 11)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $configuraciones = DB::table('configuracion')
            ->whereIn('clave', [
                'nombre_empresa',
                'cuit_empresa',
                'telefono_empresa',
                'email_contacto',
                'direccion_empresa',
            ])
            ->pluck('valor', 'clave');

        $nombre   = $configuraciones['nombre_empresa'] ?? 'Tecnosoluciones';
        $cuitRaw  = $configuraciones['cuit_empresa']   ?? null;
        $cuit     = $cuitRaw ? preg_replace('/\D/', '', $cuitRaw) : null;

        DB::table('empresas')->insert([
            'id'         => 1,
            'nombre'     => $nombre,
            'slug'       => Str::slug($nombre) ?: 'tecnosoluciones',
            'logo'       => null,
            'cuit'       => $cuit,
            'telefono'   => $configuraciones['telefono_empresa']  ?? null,
            'email'      => $configuraciones['email_contacto']    ?? null,
            'direccion'  => $configuraciones['direccion_empresa'] ?? null,
            'activa'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
