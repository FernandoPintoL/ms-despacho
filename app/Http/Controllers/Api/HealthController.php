<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\MlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MlService $mlService
    ) {}

    /**
     * Verificar estado del servicio
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'ms-despacho',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Verificar conexión con todos los microservicios
     * 
     * @return JsonResponse
     */
    public function microservices(): JsonResponse
    {
        $services = [
            'auth' => $this->verificarAuth(),
            'websocket' => $this->verificarWebSocket(),
            'decision' => $this->verificarDecision(),
            'ml' => $this->verificarML(),
        ];

        $todosDisponibles = collect($services)->every(fn($s) => $s['disponible']);

        return response()->json([
            'status' => $todosDisponibles ? 'ok' : 'degraded',
            'services' => $services,
            'timestamp' => now()->toIso8601String(),
        ], $todosDisponibles ? 200 : 503);
    }

    /**
     * Verificar MS Autenticación
     */
    private function verificarAuth(): array
    {
        $disponible = $this->authService->verificarEstado();
        
        return [
            'nombre' => 'MS Autenticación',
            'url' => config('services.auth.url'),
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Servicio disponible' : 'Servicio no disponible',
        ];
    }

    /**
     * Verificar MS WebSocket
     */
    private function verificarWebSocket(): array
    {
        try {
            $url = config('services.websocket.url');
            $response = Http::timeout(5)->get("{$url}/health");
            $disponible = $response->successful();
        } catch (\Exception $e) {
            $disponible = false;
        }

        return [
            'nombre' => 'MS WebSocket',
            'url' => config('services.websocket.url'),
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Servicio disponible' : 'Servicio no disponible',
        ];
    }

    /**
     * Verificar MS Decisión
     */
    private function verificarDecision(): array
    {
        try {
            $url = config('services.decision.url');
            $response = Http::timeout(5)->get("{$url}/api/health");
            $disponible = $response->successful();
        } catch (\Exception $e) {
            $disponible = false;
        }

        return [
            'nombre' => 'MS Decisión',
            'url' => config('services.decision.url'),
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Servicio disponible' : 'Servicio no disponible',
        ];
    }

    /**
     * Verificar Servicio ML
     */
    private function verificarML(): array
    {
        $disponible = $this->mlService->verificarEstado();

        return [
            'nombre' => 'Servicio ML',
            'url' => config('services.ml.url'),
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Servicio disponible' : 'Servicio no disponible (usando fallback)',
        ];
    }
}
