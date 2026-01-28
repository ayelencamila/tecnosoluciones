<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para estados de comprobante
 * Tabla: estados_comprobante
 */
class EstadoComprobante extends Model
{
    protected $table = 'estados_comprobante';
    protected $primaryKey = 'estado_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener comprobantes con este estado
     */
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'estado_comprobante_id', 'estado_id');
    }

    /**
     * Buscar por nombre
     */
    public static function porNombre(string $nombre): ?self
    {
        return static::where('nombre', $nombre)->first();
    }

    /**
     * Obtener estado EMITIDO
     */
    public static function emitido(): ?self
    {
        return static::porNombre('EMITIDO');
    }

    /**
     * Obtener estado ANULADO
     */
    public static function anulado(): ?self
    {
        return static::porNombre('ANULADO');
    }

    /**
     * Obtener estado REEMPLAZADO
     */
    public static function reemplazado(): ?self
    {
        return static::porNombre('REEMPLAZADO');
    }
}
