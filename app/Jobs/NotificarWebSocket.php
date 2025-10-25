<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarWebSocket implements ShouldQueue
{
    use Queueable;

    public string $evento;
    public array $datos;
    public ?int $despachoId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $evento, array $datos, ?int $despachoId = null)
    {
        $this->evento = $evento;
        $this->datos = $datos;
        $this->despachoId = $despachoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $wsUrl = config('services.websocket.url');
            
            $payload = [
                'evento' => $this->evento,
                'datos' => $this->datos,
                'despacho_id' => $this->despachoId,
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::timeout(5)
                ->post("{$wsUrl}/api/notificar", $payload);

            if ($response->successful()) {
                Log::info('Notificación WebSocket enviada', [
                    'evento' => $this->evento,
                    'despacho_id' => $this->despachoId
                ]);
            } else {
                Log::warning('Error al enviar notificación WebSocket', [
                    'evento' => $this->evento,
                    'status' => $response->status()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Excepción al notificar WebSocket', [
                'evento' => $this->evento,
                'error' => $e->getMessage()
            ]);
            
            // No relanzar la excepción para evitar reintentos innecesarios
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;
}
