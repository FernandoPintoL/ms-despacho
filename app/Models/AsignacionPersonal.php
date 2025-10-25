<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionPersonal extends Model
{
    use HasFactory;

    protected $table = 'asignacion_personal';

    protected $fillable = [
        'despacho_id',
        'personal_id',
        'rol_asignado',
        'es_responsable',
    ];

    protected $casts = [
        'es_responsable' => 'boolean',
    ];

    /**
     * Relación con despacho
     */
    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }

    /**
     * Relación con personal
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }
}
