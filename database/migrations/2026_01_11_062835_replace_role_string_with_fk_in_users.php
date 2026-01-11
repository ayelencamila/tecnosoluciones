<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('email')
                ->constrained('roles', 'rol_id')->onDelete('restrict');
        });
        DB::statement("UPDATE users u JOIN roles r ON u.role = r.nombre SET u.rol_id = r.rol_id");
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->unsignedBigInteger('rol_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('vendedor');
        });
        DB::statement("UPDATE users u JOIN roles r ON u.rol_id = r.rol_id SET u.role = r.nombre");
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rol_id']);
            $table->dropColumn('rol_id');
        });
    }
};
