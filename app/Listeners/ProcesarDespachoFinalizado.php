<?php

namespace App\Listeners;

use App\Events\DespachoFinalizado;
use App\Jobs\EnviarDatosML;
use App\Jobs\NotificarMSDecision;
use App\Jobs\NotificarWebSocket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcesarDespachoFinalizado implements ShouldQueue
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
    public function handle(DespachoFinalizado $event): void
    {
        // Enviar notificación a WebSocket
        NotificarWebSocket::dispatch(
            'despacho.finalizado',
            [
                'id' => $event->despacho->id,
                'resultado' => $event->resultado,
                'tiempo_real_min' => $event->despacho->tiempo_real_min,
                'tiempo_estimado_min' => $event->despacho->tiempo_estimado_min,
            ],
            $event->despacho->id
        );

        // Notificar a MS Decisión
        NotificarMSDecision::dispatch(
            $event->despacho,
            'despacho_finalizado'
        );

        // Enviar datos a ML para reentrenamiento (solo si fue completado)
        if ($event->resultado === 'completado') {
            EnviarDatosML::dispatch($event->despacho);
        }
    }
}
