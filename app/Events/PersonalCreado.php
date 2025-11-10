<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalCreado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Personal $personal;

    /**
     * Create a new event instance.
     */
    public function __construct(Personal $personal)
    {
        $this->personal = $personal;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('personal'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->personal->id,
            'nombre_completo' => $this->personal->nombre_completo,
            'rol' => $this->personal->rol,
            'estado' => $this->personal->estado,
            'fecha_creacion' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'personal.creado';
    }
}
