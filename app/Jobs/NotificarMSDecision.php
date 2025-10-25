<?php

namespace App\Jobs;

use App\Models\Despacho;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarMSDecision implements ShouldQueue
{
    use Queueable;

    public Despacho $despacho;
    public string $evento;

    /**
     * Create a new job instance.
     */
    public function __construct(Despacho $despacho, string $evento)
    {
        $this->despacho = $despacho;
        $this->evento = $evento;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $decisionUrl = config('services.decision.url');
            
            $payload = [
                'evento' => $this->evento,
                'despacho_id' => $this->despacho->id,
                'solicitud_id' => $this->despacho->solicitud_id,
                'estado' => $this->despacho->estado,
                'ambulancia' => [
                    'id' => $this->despacho->ambulancia->id,
                    'placa' => $this->despacho->ambulancia->placa,
                ],
                'tiempo_real_min' => $this->despacho->tiempo_real_min,
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::timeout(10)
                ->post("{$decisionUrl}/api/webhook/despacho", $payload);

            if ($response->successful()) {
                Log::info('Notificación enviada a MS Decisión', [
                    'evento' => $this->evento,
                    'despacho_id' => $this->despacho->id
                ]);
            } else {
                Log::warning('Error al notificar MS Decisión', [
                    'evento' => $this->evento,
                    'status' => $response->status()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Excepción al notificar MS Decisión', [
                'evento' => $this->evento,
                'despacho_id' => $this->despacho->id,
                'error' => $e->getMessage()
            ]);
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
    public $backoff = 10;
}
