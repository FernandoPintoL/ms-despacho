<?php

namespace App\Events;

use App\Models\Despacho;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoEstadoCambiado
{
    use Dispatchable, SerializesModels;

    public Despacho $despacho;
    public string $estadoAnterior;
    public string $estadoNuevo;

    /**
     * Create a new event instance.
     */
    public function __construct(Despacho $despacho, string $estadoAnterior, string $estadoNuevo)
    {
        $this->despacho = $despacho;
        $this->estadoAnterior = $estadoAnterior;
        $this->estadoNuevo = $estadoNuevo;
    }
}
