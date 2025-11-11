<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalCreado
{
    use Dispatchable, SerializesModels;

    public Personal $personal;

    /**
     * Create a new event instance.
     */
    public function __construct(Personal $personal)
    {
        $this->personal = $personal;
    }
}
