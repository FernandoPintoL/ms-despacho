<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalActualizado
{
    use Dispatchable, SerializesModels;

    public Personal $personal;
    public array $cambios;

    /**
     * Create a new event instance.
     */
    public function __construct(Personal $personal, array $cambios = [])
    {
        $this->personal = $personal;
        $this->cambios = $cambios;
    }

}
