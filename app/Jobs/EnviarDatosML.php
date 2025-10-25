<?php

namespace App\Jobs;

use App\Models\Despacho;
use App\Services\MlService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarDatosML implements ShouldQueue
{
    use Queueable;

    public Despacho $despacho;

    /**
     * Create a new job instance.
     */
    public function __construct(Despacho $despacho)
    {
        $this->despacho = $despacho;
    }

    /**
     * Execute the job.
     */
    public function handle(MlService $mlService): void
    {
        try {
            // Preparar datos del despacho completado para reentrenamiento
            $datosDespacho = [
                'despacho_id' => $this->despacho->id,
                'distancia_km' => $this->despacho->distancia_km,
                'tiempo_estimado_min' => $this->despacho->tiempo_estimado_min,
                'tiempo_real_min' => $this->despacho->tiempo_real_min,
                'tipo_ambulancia' => $this->despacho->ambulancia->tipo_ambulancia ?? null,
                'prioridad' => $this->despacho->prioridad,
                'incidente' => $this->despacho->incidente,
                'fecha_solicitud' => $this->despacho->fecha_solicitud?->toIso8601String(),
                'fecha_finalizacion' => $this->despacho->fecha_finalizacion?->toIso8601String(),
            ];

            $mlService->enviarDatosReentrenamiento($datosDespacho);

            Log::info('Datos enviados a ML para reentrenamiento', [
                'despacho_id' => $this->despacho->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar datos a ML', [
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
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;
}
