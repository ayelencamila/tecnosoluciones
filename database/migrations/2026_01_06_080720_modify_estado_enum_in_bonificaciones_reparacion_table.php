<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega el estado intermedio 'esperando_cliente' al flujo de bonificaciones.
     * Este estado se usa cuando el admin aprueba la bonificación pero aún no se ha
     * notificado al cliente (antes del envío de WhatsApp).
     * 
     * Flujo completo de estados:
     * 1. pendiente → bonificación calculada por el sistema, esperando revisión admin
     * 2. aprobada → admin aprobó, se envía notificación al cliente vía WhatsApp
     * 3. esperando_cliente → notificación enviada, esperando que cliente responda
     * 4. rechazada → admin rechazó la bonificación (no llega al cliente)
     * 
     * Nota: decision_cliente maneja las respuestas del cliente ('aceptar', 'cancelar', 'pendiente')
     */
    public function up(): void
    {
        // En MySQL no se puede modificar ENUM directamente, hay que recrear la columna
        DB::statement("ALTER TABLE bonificaciones_reparacion MODIFY COLUMN estado ENUM('pendiente', 'aprobada', 'rechazada', 'esperando_cliente') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al ENUM original
        DB::statement("ALTER TABLE bonificaciones_reparacion MODIFY COLUMN estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente'");
    }
};
