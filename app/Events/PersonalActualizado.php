<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('personal'),
            new Channel("personal.{$this->personal->id}"),
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
            'cambios' => $this->cambios,
            'fecha_actualizacion' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'personal.actualizado';
    }
}
