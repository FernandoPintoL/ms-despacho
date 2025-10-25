<?php

namespace App\Models;

use App\Events\DespachoEstadoCambiado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Despacho extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'despachos';

    protected $fillable = [
        'solicitud_id',
        'ambulancia_id',
        'fecha_solicitud',
        'fecha_asignacion',
        'fecha_llegada',
        'fecha_finalizacion',
        'ubicacion_origen_lat',
        'ubicacion_origen_lng',
        'direccion_origen',
        'ubicacion_destino_lat',
        'ubicacion_destino_lng',
        'direccion_destino',
        'distancia_km',
        'tiempo_estimado_min',
        'tiempo_real_min',
        'estado',
        'incidente',
        'decision',
        'prioridad',
        'observaciones',
        'datos_adicionales',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_asignacion' => 'datetime',
        'fecha_llegada' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'ubicacion_origen_lat' => 'decimal:8',
        'ubicacion_origen_lng' => 'decimal:8',
        'ubicacion_destino_lat' => 'decimal:8',
        'ubicacion_destino_lng' => 'decimal:8',
        'distancia_km' => 'decimal:2',
        'tiempo_estimado_min' => 'integer',
        'tiempo_real_min' => 'integer',
        'datos_adicionales' => 'array',
    ];

    /**
     * Relación con ambulancia
     */
    public function ambulancia()
    {
        return $this->belongsTo(Ambulancia::class);
    }

    /**
     * Relación con personal asignado
     */
    public function personalAsignado()
    {
        return $this->belongsToMany(Personal::class, 'asignacion_personal')
            ->withPivot('rol_asignado', 'es_responsable')
            ->withTimestamps();
    }

    /**
     * Relación con asignaciones de personal
     */
    public function asignaciones()
    {
        return $this->hasMany(AsignacionPersonal::class);
    }

    /**
     * Relación con historial de rastreo
     */
    public function historialRastreo()
    {
        return $this->hasMany(HistorialRastreo::class);
    }

    /**
     * Scope para despachos activos
     */
    public function scopeActivos($query)
    {
        return $query->whereIn('estado', ['asignado', 'en_camino', 'en_sitio', 'trasladando']);
    }

    /**
     * Scope por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope por prioridad
     */
    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Verificar si está activo
     */
    public function estaActivo(): bool
    {
        return in_array($this->estado, ['asignado', 'en_camino', 'en_sitio', 'trasladando']);
    }

    /**
     * Cambiar estado
     */
    public function cambiarEstado(string $nuevoEstado): void
    {
        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);
        
        // Disparar evento
        event(new DespachoEstadoCambiado($this, $estadoAnterior, $nuevoEstado));
    }

    /**
     * Calcular tiempo real
     */
    public function calcularTiempoReal(): ?int
    {
        if ($this->fecha_asignacion && $this->fecha_llegada) {
            return $this->fecha_asignacion->diffInMinutes($this->fecha_llegada);
        }
        return null;
    }

    /**
     * Obtener responsable del despacho
     */
    public function getResponsableAttribute()
    {
        return $this->personalAsignado()
            ->wherePivot('es_responsable', true)
            ->first();
    }
}
