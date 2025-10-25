<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personal';

    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'rol',
        'especialidad',
        'experiencia',
        'estado',
        'telefono',
        'email',
    ];

    protected $casts = [
        'experiencia' => 'integer',
    ];

    /**
     * Relación con asignaciones
     */
    public function asignaciones()
    {
        return $this->hasMany(AsignacionPersonal::class);
    }

    /**
     * Relación con despachos a través de asignaciones
     */
    public function despachos()
    {
        return $this->belongsToMany(Despacho::class, 'asignacion_personal')
            ->withPivot('rol_asignado', 'es_responsable')
            ->withTimestamps();
    }

    /**
     * Scope para personal disponible
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible');
    }

    /**
     * Scope por rol
     */
    public function scopePorRol($query, $rol)
    {
        return $query->where('rol', $rol);
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
     * Obtener nombre completo
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
