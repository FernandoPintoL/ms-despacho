<?php

namespace App\Listeners;

use App\Events\DespachoEstadoCambiado;
use App\Jobs\NotificarWebSocket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EnviarNotificacionEstadoCambiado implements ShouldQueue
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
    public function handle(DespachoEstadoCambiado $event): void
    {
        // Enviar notificación a WebSocket
        NotificarWebSocket::dispatch(
            'despacho.estado.cambiado',
            [
                'id' => $event->despacho->id,
                'estado_anterior' => $event->estadoAnterior,
                'estado_nuevo' => $event->estadoNuevo,
                'ambulancia_id' => $event->despacho->ambulancia_id,
            ],
            $event->despacho->id
        );
    }
}
