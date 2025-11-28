<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoVenta extends Model
{
    protected $table = 'estados_venta';
    protected $primaryKey = 'estadoVentaID';
    protected $fillable = ['nombreEstado'];

    // Constantes para usar en el código sin hardcodear strings
    public const PENDIENTE = 1;
    public const COMPLETADA = 2;
    public const ANULADA = 3;
}