<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Permitir ver el listado
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Venta $venta): bool
    {
        return true; // Permitir ver el detalle
    }

    /**
     * Determine whether the user can create models.
     * (SOLUCIÓN AL ERROR 403 EN STORE)
     */
    public function create(User $user): bool
    {
        // Aquí puedes poner lógica como: return $user->role === 'vendedor';
        return true; // Por ahora, todo usuario logueado puede crear ventas
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venta $venta): bool
    {
        return false; // Las ventas no se suelen editar, se anulan
    }

    /**
     * Determine whether the user can delete the model.
     * (Usado para ANULAR en tu caso)
     */
    public function anular(User $user, Venta $venta): bool
    {
        // Solo permitir anular si no está anulada ya
        return !$venta->anulada;
    }
}
