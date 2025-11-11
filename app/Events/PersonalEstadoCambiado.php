<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalEstadoCambiado
{
    use Dispatchable, SerializesModels;

    public Personal $personal;
    public string $estadoAnterior;
    public string $estadoNuevo;

    /**
     * Create a new event instance.
     */
    public function __construct(Personal $personal, string $estadoAnterior, string $estadoNuevo)
    {
        $this->personal = $personal;
        $this->estadoAnterior = $estadoAnterior;
        $this->estadoNuevo = $estadoNuevo;
    }
}
