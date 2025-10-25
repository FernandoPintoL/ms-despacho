<?php

namespace App\Events;

use App\Models\Despacho;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoCreado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Despacho $despacho;

    /**
     * Create a new event instance.
     */
    public function __construct(Despacho $despacho)
    {
        $this->despacho = $despacho;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('despachos'),
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
            'id' => $this->despacho->id,
            'solicitud_id' => $this->despacho->solicitud_id,
            'ambulancia' => [
                'id' => $this->despacho->ambulancia->id,
                'placa' => $this->despacho->ambulancia->placa,
                'tipo' => $this->despacho->ambulancia->tipo_ambulancia,
            ],
            'estado' => $this->despacho->estado,
            'prioridad' => $this->despacho->prioridad,
            'distancia_km' => $this->despacho->distancia_km,
            'tiempo_estimado_min' => $this->despacho->tiempo_estimado_min,
            'ubicacion_origen' => [
                'lat' => $this->despacho->ubicacion_origen_lat,
                'lng' => $this->despacho->ubicacion_origen_lng,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'despacho.creado';
    }
}
