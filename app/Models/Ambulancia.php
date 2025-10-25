<?php

namespace App\Models;

use App\Events\AmbulanciaUbicacionActualizada;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ambulancia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ambulancias';

    protected $fillable = [
        'placa',
        'modelo',
        'tipo_ambulancia',
        'estado',
        'caracteristicas',
        'ubicacion_actual_lat',
        'ubicacion_actual_lng',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'caracteristicas' => 'array',
        'ubicacion_actual_lat' => 'decimal:8',
        'ubicacion_actual_lng' => 'decimal:8',
        'ultima_actualizacion' => 'datetime',
    ];

    /**
     * Relación con despachos
     */
    public function despachos()
    {
        return $this->hasMany(Despacho::class);
    }

    /**
     * Scope para ambulancias disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible');
    }

    /**
     * Scope para ambulancias en servicio
     */
    public function scopeEnServicio($query)
    {
        return $query->where('estado', 'en_servicio');
    }

    /**
     * Scope por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_ambulancia', $tipo);
    }

    /**
     * Verificar si está disponible
     */
    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible';
    }

    /**
     * Marcar como en servicio
     */
    public function marcarEnServicio(): void
    {
        $this->update(['estado' => 'en_servicio']);
    }

    /**
     * Marcar como disponible
     */
    public function marcarDisponible(): void
    {
        $this->update(['estado' => 'disponible']);
    }

    /**
     * Actualizar ubicación
     */
    public function actualizarUbicacion(float $lat, float $lng): void
    {
        $this->update([
            'ubicacion_actual_lat' => $lat,
            'ubicacion_actual_lng' => $lng,
            'ultima_actualizacion' => now(),
        ]);
        
        // Disparar evento
        event(new AmbulanciaUbicacionActualizada($this));
    }
}
