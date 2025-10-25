<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MlService
{
    private string $mlServiceUrl;
    private int $timeout;
    private bool $useFallback;

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
        $this->timeout = config('services.ml.timeout', 10);
        $this->useFallback = config('services.ml.use_fallback', true);
    }

    /**
     * Predecir tiempo de llegada usando ML
     * 
     * @param float $distanciaKm Distancia en kilómetros
     * @param string $tipoAmbulancia Tipo de ambulancia
     * @param float $traficoEstimado Estimación de tráfico (0-1)
     * @return int Tiempo estimado en minutos
     */
    public function predecirTiempoLlegada(
        float $distanciaKm,
        string $tipoAmbulancia = 'intermedia',
        float $traficoEstimado = 0.5
    ): int {
        try {
            // Preparar features para el modelo
            $features = $this->prepararFeatures($distanciaKm, $tipoAmbulancia, $traficoEstimado);

            // Intentar obtener de cache primero (si está disponible)
            try {
                $cacheKey = "ml_prediction_" . md5(json_encode($features));
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    Log::debug('Predicción ML obtenida de cache', ['tiempo' => $cached]);
                    return (int) $cached;
                }
            } catch (\Exception $e) {
                // Cache no disponible, continuar sin cache
                Log::debug('Cache no disponible, continuando sin cache');
            }

            // Llamar al servicio ML
            $response = Http::timeout($this->timeout)
                ->post("{$this->mlServiceUrl}/predict", $features);

            if ($response->successful()) {
                $tiempoEstimado = $response->json('tiempo_estimado');
                
                // Cachear por 1 hora (si está disponible)
                try {
                    Cache::put($cacheKey, $tiempoEstimado, 3600);
                } catch (\Exception $e) {
                    // Cache no disponible, continuar sin cachear
                }

                Log::info('Predicción ML exitosa', [
                    'distancia_km' => $distanciaKm,
                    'tiempo_estimado' => $tiempoEstimado
                ]);

                return (int) $tiempoEstimado;
            }

            Log::warning('Error en respuesta del servicio ML', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->fallbackEstimacion($distanciaKm);

        } catch (\Exception $e) {
            Log::error('Error al llamar servicio ML', [
                'error' => $e->getMessage(),
                'distancia_km' => $distanciaKm
            ]);

            return $this->fallbackEstimacion($distanciaKm);
        }
    }

    /**
     * Entrenar modelo con nuevos datos
     * 
     * @param array $datosEntrenamiento Array de datos históricos
     * @return bool
     */
    public function entrenarModelo(array $datosEntrenamiento): bool
    {
        try {
            $response = Http::timeout(60) // Mayor timeout para entrenamiento
                ->post("{$this->mlServiceUrl}/train", [
                    'datos' => $datosEntrenamiento
                ]);

            if ($response->successful()) {
                $resultado = $response->json();
                
                Log::info('Modelo ML entrenado exitosamente', [
                    'mse' => $resultado['mse'] ?? null,
                    'r2_score' => $resultado['r2_score'] ?? null
                ]);

                // Limpiar cache de predicciones (si está disponible)
                try {
                    Cache::flush();
                } catch (\Exception $e) {
                    // Cache no disponible
                }

                return true;
            }

            Log::warning('Error al entrenar modelo ML', [
                'status' => $response->status()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error al entrenar modelo ML', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Evaluar precisión del modelo
     * 
     * @return array|null Métricas de evaluación
     */
    public function evaluarModelo(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->mlServiceUrl}/evaluate");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error al evaluar modelo ML', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verificar estado del servicio ML
     * 
     * @return bool
     */
    public function verificarEstado(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->mlServiceUrl}/health");

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Servicio ML no disponible', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Preparar features para el modelo ML
     * 
     * @param float $distanciaKm
     * @param string $tipoAmbulancia
     * @param float $traficoEstimado
     * @return array
     */
    private function prepararFeatures(
        float $distanciaKm,
        string $tipoAmbulancia,
        float $traficoEstimado
    ): array {
        $now = now();

        // Mapear tipo de ambulancia a número
        $tipoMap = [
            'basica' => 0,
            'intermedia' => 1,
            'avanzada' => 2,
            'uci' => 3,
        ];

        return [
            'distancia' => $distanciaKm,
            'hora_dia' => $now->hour,
            'dia_semana' => $now->dayOfWeek,
            'tipo_ambulancia' => $tipoMap[$tipoAmbulancia] ?? 1,
            'trafico_estimado' => $traficoEstimado,
        ];
    }

    /**
     * Estimación fallback si el servicio ML no está disponible
     * Usa una fórmula simple basada en velocidad promedio
     * 
     * @param float $distanciaKm
     * @return int Tiempo en minutos
     */
    private function fallbackEstimacion(float $distanciaKm): int
    {
        if (!$this->useFallback) {
            throw new \RuntimeException('Servicio ML no disponible y fallback deshabilitado');
        }

        $now = now();
        $hora = $now->hour;

        // Velocidad promedio según hora del día
        $velocidadPromedio = match (true) {
            $hora >= 7 && $hora <= 9 => 25,   // Hora pico mañana
            $hora >= 12 && $hora <= 14 => 30, // Mediodía
            $hora >= 18 && $hora <= 20 => 25, // Hora pico tarde
            $hora >= 22 || $hora <= 5 => 50,  // Madrugada
            default => 40,                     // Resto del día
        };

        $tiempoHoras = $distanciaKm / $velocidadPromedio;
        $tiempoMinutos = (int) ceil($tiempoHoras * 60);

        Log::info('Usando estimación fallback', [
            'distancia_km' => $distanciaKm,
            'velocidad_promedio' => $velocidadPromedio,
            'tiempo_estimado' => $tiempoMinutos
        ]);

        return $tiempoMinutos;
    }

    /**
     * Enviar datos de despacho completado para reentrenamiento
     * 
     * @param array $datosDespacho
     * @return bool
     */
    public function enviarDatosReentrenamiento(array $datosDespacho): bool
    {
        try {
            // Enviar de forma asíncrona (no bloqueante)
            Http::timeout(5)
                ->post("{$this->mlServiceUrl}/feedback", $datosDespacho);

            Log::info('Datos enviados para reentrenamiento', [
                'despacho_id' => $datosDespacho['despacho_id'] ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            // No es crítico si falla
            Log::debug('No se pudieron enviar datos de reentrenamiento', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener estadísticas del modelo ML
     * 
     * @return array|null
     */
    public function obtenerEstadisticas(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->mlServiceUrl}/stats");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas ML', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Predecir múltiples tiempos de llegada en batch
     * 
     * @param array $predicciones Array de features
     * @return array
     */
    public function predecirBatch(array $predicciones): array
    {
        try {
            $response = Http::timeout($this->timeout * 2)
                ->post("{$this->mlServiceUrl}/predict/batch", [
                    'predicciones' => $predicciones
                ]);

            if ($response->successful()) {
                return $response->json('resultados', []);
            }

            // Fallback: procesar uno por uno
            return array_map(function ($pred) {
                return $this->fallbackEstimacion($pred['distancia']);
            }, $predicciones);

        } catch (\Exception $e) {
            Log::error('Error en predicción batch', [
                'error' => $e->getMessage()
            ]);

            // Fallback
            return array_map(function ($pred) {
                return $this->fallbackEstimacion($pred['distancia']);
            }, $predicciones);
        }
    }
}
