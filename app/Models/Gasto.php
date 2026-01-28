<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';
    protected $primaryKey = 'gasto_id';

    protected $fillable = [
        'categoria_gasto_id',
        'fecha',
        'descripcion',
        'monto',
        'comprobante',
        'observaciones',
        'usuario_id',
        'anulado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'anulado' => 'boolean',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id', 'categoria_gasto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('anulado', false);
    }

    public function scopeDelMes($query, $mes, $anio)
    {
        return $query->whereMonth('fecha', $mes)->whereYear('fecha', $anio);
    }

    public function scopeGastos($query)
    {
        return $query->whereHas('categoria', function ($q) {
            $q->where('tipo', 'gasto');
        });
    }

    public function scopePerdidas($query)
    {
        return $query->whereHas('categoria', function ($q) {
            $q->where('tipo', 'perdida');
        });
    }

    // Accessors
    public function getTipoAttribute()
    {
        return $this->categoria?->tipo ?? 'gasto';
    }
}
