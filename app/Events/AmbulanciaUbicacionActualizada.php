<?php

namespace App\Events;

use App\Models\Ambulancia;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmbulanciaUbicacionActualizada
{
    use Dispatchable, SerializesModels;

    public Ambulancia $ambulancia;

    /**
     * Create a new event instance.
     */
    public function __construct(Ambulancia $ambulancia)
    {
        $this->ambulancia = $ambulancia;
    }
}
