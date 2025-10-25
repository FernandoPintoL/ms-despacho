<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMicroserviceToken
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener token del header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token no proporcionado',
                'message' => 'Se requiere autenticación. Incluya el token en el header Authorization: Bearer {token}'
            ], 401);
        }

        // Verificar token con MS Autenticación
        $userData = $this->authService->verificarToken($token);

        if (!$userData) {
            return response()->json([
                'error' => 'Token inválido o expirado',
                'message' => 'El token proporcionado no es válido o ha expirado'
            ], 401);
        }

        // Agregar datos del usuario al request
        $request->merge(['auth_user' => $userData]);
        $request->attributes->set('user_id', $userData['id'] ?? null);
        $request->attributes->set('user_email', $userData['email'] ?? null);
        $request->attributes->set('user_role', $userData['role'] ?? null);

        return $next($request);
    }
}
