<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    private string $authServiceUrl;
    private int $timeout;
    private string $verifyEndpoint;

    public function __construct()
    {
        $this->authServiceUrl = config('services.auth.url');
        $this->timeout = config('services.auth.timeout');
        $this->verifyEndpoint = config('services.auth.verify_endpoint');
    }

    /**
     * Verificar token con MS Autenticación
     * 
     * @param string $token
     * @return array|null Datos del usuario si el token es válido
     */
    public function verificarToken(string $token): ?array
    {
        try {
            // Intentar obtener de cache primero (cache por 5 minutos)
            $cacheKey = "auth_token_" . md5($token);
            
            try {
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    Log::debug('Token verificado desde cache');
                    return $cached;
                }
            } catch (\Exception $e) {
                // Cache no disponible, continuar sin cache
            }

            // Llamar al MS Autenticación
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->authServiceUrl}{$this->verifyEndpoint}");

            if ($response->successful()) {
                $userData = $response->json();
                
                // Cachear resultado por 5 minutos
                try {
                    Cache::put($cacheKey, $userData, 300);
                } catch (\Exception $e) {
                    // Cache no disponible
                }

                Log::info('Token verificado exitosamente', [
                    'user_id' => $userData['id'] ?? null
                ]);

                return $userData;
            }

            if ($response->status() === 401) {
                Log::warning('Token inválido o expirado');
                return null;
            }

            Log::warning('Error al verificar token con MS Autenticación', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción al verificar token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verificar estado del servicio de autenticación
     * 
     * @return bool
     */
    public function verificarEstado(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->authServiceUrl}/api/health");

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('MS Autenticación no disponible', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener información del usuario por ID
     * 
     * @param int $userId
     * @param string $token
     * @return array|null
     */
    public function obtenerUsuario(int $userId, string $token): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->authServiceUrl}/api/users/{$userId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error al obtener usuario', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
