<?php

namespace App\Events;

use App\Models\Ambulancia;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmbulanciaUbicacionActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ambulancia $ambulancia;

    /**
     * Create a new event instance.
     */
    public function __construct(Ambulancia $ambulancia)
    {
        $this->ambulancia = $ambulancia;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('ambulancias'),
            new Channel("ambulancia.{$this->ambulancia->id}"),
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
            'id' => $this->ambulancia->id,
            'placa' => $this->ambulancia->placa,
            'ubicacion' => [
                'lat' => $this->ambulancia->ubicacion_actual_lat,
                'lng' => $this->ambulancia->ubicacion_actual_lng,
            ],
            'estado' => $this->ambulancia->estado,
            'ultima_actualizacion' => $this->ambulancia->ultima_actualizacion?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'ambulancia.ubicacion.actualizada';
    }
}
