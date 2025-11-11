<?php

namespace App\Events;

use App\Models\Despacho;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoFinalizado
{
    use Dispatchable, SerializesModels;

    public Despacho $despacho;
    public string $resultado;

    /**
     * Create a new event instance.
     */
    public function __construct(Despacho $despacho, string $resultado)
    {
        $this->despacho = $despacho;
        $this->resultado = $resultado;
    }
}
