<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Arquitectura comercial: planes y sus limites de uso.
 *
 * Decisiones de modelado
 * ----------------------
 * 1. Los limites van en una tabla aparte y no en columnas de `planes`.
 *    Agregar un limite nuevo (depositos, productos, reparaciones) no deberia
 *    exigir una migracion, y sobre todo AJUSTAR un numero no deberia exigir
 *    un deploy: es una decision comercial, no tecnica.
 *
 * 2. `valor` es NULLABLE y NULL significa SIN LIMITE. No se usa un centinela
 *    tipo 999999: siguiendo a Elmasri & Navathe, NULL es el valor correcto
 *    para "no aplica", y un centinela tarde o temprano se filtra en una
 *    comparacion y alguien descubre que su plan "ilimitado" tenia tope.
 *
 * 3. `tipo` distingue limites DUROS de BLANDOS, y es la decision mas
 *    importante del diseño:
 *
 *    - DURO (capacidad: usuarios, depositos). Al alcanzarlo se bloquea la
 *      creacion. Es inofensivo: crear un usuario es una accion
 *      administrativa y planificada, quien la intenta ve "subi de plan" y
 *      sigue trabajando.
 *
 *    - BLANDO (volumen operativo: ventas, reparaciones). Se cuenta y se
 *      avisa, pero NUNCA se bloquea. Frenar la venta numero 101 significa
 *      que el local no puede cobrar un martes a las 18 con gente en el
 *      mostrador: el cliente pierde la venta y la culpa es del sistema.
 *      Ningun SaaS serio bloquea la accion que le da de comer al cliente.
 *
 * 4. `empresas.plan_id` es NULLABLE y NULL significa sin plan asignado, que
 *    se trata como sin limites. Asi las empresas que ya existen no quedan
 *    bloqueadas por una migracion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            // Identificador estable para referirse al plan desde el codigo
            // sin depender del nombre comercial, que puede cambiar.
            $table->string('slug', 60)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio_mensual', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            // Orden de presentacion en la grilla de precios, de menor a mayor.
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_limites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            // Clave del limite (ej: 'usuarios', 'ventas_mensuales'). El
            // catalogo de claves conocidas vive en config/planes.php, que es
            // donde ademas se define COMO se cuenta cada una.
            $table->string('clave', 60);
            $table->enum('tipo', ['duro', 'blando'])->default('duro');
            // NULL = sin limite. Ver nota 2 de la cabecera.
            $table->unsignedInteger('valor')->nullable();
            $table->timestamps();

            // Un plan no puede definir dos veces el mismo limite.
            $table->unique(['plan_id', 'clave']);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('activa')
                ->constrained('planes')
                // RESTRICT: no se puede borrar un plan que alguien esta
                // usando. Borrarlo en cascada dejaria empresas sin plan sin
                // que nadie lo decidiera.
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });

        Schema::dropIfExists('plan_limites');
        Schema::dropIfExists('planes');
    }
};
