<?php

namespace App\Events;

use App\Models\Despacho;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoFinalizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('despachos'),
            new Channel("despacho.{$this->despacho->id}"),
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
            'resultado' => $this->resultado,
            'tiempo_real_min' => $this->despacho->tiempo_real_min,
            'tiempo_estimado_min' => $this->despacho->tiempo_estimado_min,
            'distancia_km' => $this->despacho->distancia_km,
            'ambulancia_id' => $this->despacho->ambulancia_id,
            'fecha_finalizacion' => $this->despacho->fecha_finalizacion?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'despacho.finalizado';
    }
}
