<?php

namespace App\Services;

use Predis\Client as RedisClient;
use Illuminate\Support\Facades\Log;

/**
 * Redis Event Publisher
 *
 * Publica eventos a Redis para que otros servicios los consuman
 * (principalmente ms_websocket para notificaciones en tiempo real)
 */
class RedisEventPublisher
{
    protected $redis;

    public function __construct()
    {
        try {
            $this->redis = new RedisClient([
                'scheme' => 'tcp',
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'password' => env('REDIS_PASSWORD', null) ?: null,
                'database' => env('REDIS_DB', 0),
            ]);

            // Probar conexión
            $this->redis->ping();
            Log::info('✅ Redis conectado exitosamente');
        } catch (\Exception $e) {
            Log::error('❌ Error conectando a Redis: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Publicar evento de despacho creado
     */
    public function publishDispatchCreated($dispatch)
    {
        $this->publish('despacho.created', [
            'id' => $dispatch->id,
            'numero' => $dispatch->numero ?? null,
            'estado' => $dispatch->estado ?? null,
            'usuario_id' => $dispatch->usuario_id ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Publicar evento de despacho actualizado
     */
    public function publishDispatchUpdated($dispatch)
    {
        $this->publish('despacho.updated', [
            'id' => $dispatch->id,
            'numero' => $dispatch->numero ?? null,
            'estado' => $dispatch->estado ?? null,
            'usuario_id' => $dispatch->usuario_id ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Publicar evento de despacho asignado
     */
    public function publishDispatchAssigned($dispatch, $ambulancia)
    {
        $this->publish('despacho.assigned', [
            'dispatch_id' => $dispatch->id,
            'ambulancia_id' => $ambulancia->id ?? null,
            'estado' => 'assigned',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Publicar evento genérico
     *
     * @param string $eventType - Tipo de evento (ej: 'despacho.created')
     * @param array $data - Datos del evento
     */
    protected function publish($eventType, $data)
    {
        try {
            $message = json_encode([
                'type' => $eventType,
                'data' => $data,
                'service' => 'ms-despacho',
                'timestamp' => now()->toIso8601String(),
            ]);

            // Publicar en el canal despacho:events
            $result = $this->redis->publish('despacho:events', $message);

            Log::info("📢 Evento publicado: {$eventType}", [
                'subscribers' => $result,
                'data' => $data,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("❌ Error publicando evento {$eventType}: " . $e->getMessage());
            // No lanzar excepción para no romper el flujo principal
            return false;
        }
    }

    /**
     * Verificar conexión a Redis
     */
    public function healthCheck()
    {
        try {
            $this->redis->ping();
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Redis no disponible: ' . $e->getMessage());
            return false;
        }
    }
}
