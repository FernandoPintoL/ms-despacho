<?php

namespace App\Listeners;

use App\Events\DespachoCreado;
use App\Jobs\NotificarMSDecision;
use App\Jobs\NotificarWebSocket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EnviarNotificacionDespachoCreado implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DespachoCreado $event): void
    {
        // Enviar notificación a WebSocket
        NotificarWebSocket::dispatch(
            'despacho.creado',
            [
                'id' => $event->despacho->id,
                'ambulancia_placa' => $event->despacho->ambulancia->placa,
                'estado' => $event->despacho->estado,
                'prioridad' => $event->despacho->prioridad,
            ],
            $event->despacho->id
        );

        // Notificar a MS Decisión
        NotificarMSDecision::dispatch(
            $event->despacho,
            'despacho_creado'
        );
    }
}
