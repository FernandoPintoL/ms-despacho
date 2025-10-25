<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialRastreo extends Model
{
    use HasFactory;

    protected $table = 'historial_rastreo';

    protected $fillable = [
        'despacho_id',
        'latitud',
        'longitud',
        'velocidad',
        'altitud',
        'precision',
        'timestamp_gps',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'velocidad' => 'decimal:2',
        'altitud' => 'decimal:2',
        'precision' => 'decimal:2',
        'timestamp_gps' => 'datetime',
    ];

    /**
     * Relación con despacho
     */
    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }

    /**
     * Scope para obtener última ubicación
     */
    public function scopeUltimaUbicacion($query, $despachoId)
    {
        return $query->where('despacho_id', $despachoId)
            ->orderBy('timestamp_gps', 'desc')
            ->first();
    }
}
