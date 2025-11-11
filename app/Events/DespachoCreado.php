<?php

namespace App\Events;

use App\Models\Despacho;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoCreado
{
    use Dispatchable, SerializesModels;

    public Despacho $despacho;

    /**
     * Create a new event instance.
     */
    public function __construct(Despacho $despacho)
    {
        $this->despacho = $despacho;
    }
}
