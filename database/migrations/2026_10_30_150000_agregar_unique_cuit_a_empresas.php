<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El CUIT es clave candidata de `empresas`: identifica unívocamente al
 * contribuyente, así que dos filas con el mismo CUIT serían el mismo cliente
 * cargado dos veces.
 *
 * Siguiendo a Elmasri & Navathe, una clave candidata se declara UNIQUE en el
 * esquema y no solo se valida en la aplicación: la restricción pertenece al
 * modelo de datos, y dejarla únicamente en el FormRequest la volvería
 * opcional para cualquier otra vía de escritura (un seeder, un comando, una
 * importación).
 *
 * Queda nullable a propósito: al dar de alta un cliente puede no estar el
 * CUIT a mano todavía, y en MySQL un índice UNIQUE admite varios NULL, que es
 * la semántica correcta — "no se sabe" no colisiona con "no se sabe".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Normaliza las cadenas vacías a NULL antes de crear el índice: '' y
        // '' SÍ colisionan, y además "vacío" no es un CUIT, es un dato ausente.
        Schema::getConnection()
            ->table('empresas')
            ->whereRaw("TRIM(COALESCE(cuit, '')) = ''")
            ->update(['cuit' => null]);

        Schema::table('empresas', function (Blueprint $table) {
            $table->unique('cuit', 'empresas_cuit_unique');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropUnique('empresas_cuit_unique');
        });
    }
};
