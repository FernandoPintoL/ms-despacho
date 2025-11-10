<?php

namespace App\Events;

use App\Models\Personal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonalEstadoCambiado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
            'estado_anterior' => $this->estadoAnterior,
            'estado_nuevo' => $this->estadoNuevo,
            'fecha_cambio' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'personal.estado.cambiado';
    }
}
