<?php

namespace App\Http\Controllers;

use App\Services\RedisEventPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Despacho GraphQL Controller
 *
 * Controlador para manejar operaciones de despacho expuestas via GraphQL
 * Utiliza RedisEventPublisher para publicar eventos
 */
class DespachoGraphQLController extends Controller
{
    protected $eventPublisher;

    public function __construct(RedisEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Crear un nuevo despacho
     *
     * Este método es llamado por los resolvers GraphQL
     * Publicará eventos para que WebSocket notifique a clientes
     */
    public function createDespacho(Request $request)
    {
        try {
            // Validar datos (esto lo hace Apollo también, pero es good practice)
            $validated = $request->validate([
                'numero' => 'required|string|unique:despachos',
                'estado' => 'required|string',
                'usuario_id' => 'required|integer',
                'ubicacion' => 'nullable|string',
                'descripcion' => 'nullable|string',
            ]);

            Log::info('📥 Request para crear despacho', $validated);

            // 1. Crear despacho en BD
            // Nota: Esto debería estar en un modelo/repository, aquí es simplificado
            $despacho = [
                'id' => uniqid(),
                'numero' => $validated['numero'],
                'estado' => $validated['estado'],
                'usuario_id' => $validated['usuario_id'],
                'ubicacion' => $validated['ubicacion'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
                'created_at' => now()->toIso8601String(),
            ];

            // 2. Guardar en BD (aquí iría tu lógica de BD real)
            // $despacho = Despacho::create($validated);

            Log::info('✅ Despacho creado en BD', $despacho);

            // 3. IMPORTANTE: Publicar evento en Redis
            // Esto notificará a WebSocket y otros servicios interesados
            $this->eventPublisher->publishDispatchCreated((object) $despacho);

            Log::info('✅ Evento publicado a Redis');

            // 4. Retornar despacho (GraphQL lo formateará según el schema)
            return response()->json($despacho, 201);
        } catch (\Exception $e) {
            Log::error('❌ Error creando despacho: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un despacho
     */
    public function updateDespacho(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'estado' => 'nullable|string',
                'ubicacion' => 'nullable|string',
            ]);

            Log::info("📥 Request para actualizar despacho {$id}", $validated);

            // 1. Buscar y actualizar despacho
            // $despacho = Despacho::findOrFail($id);
            // $despacho->update($validated);

            $despacho = [
                'id' => $id,
                'estado' => $validated['estado'] ?? 'pendiente',
                'updated_at' => now()->toIso8601String(),
            ];

            // 2. Publicar evento de actualización
            $this->eventPublisher->publishDispatchUpdated((object) $despacho);

            Log::info('✅ Evento de actualización publicado');

            return response()->json($despacho);
        } catch (\Exception $e) {
            Log::error("❌ Error actualizando despacho: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Asignar despacho a una ambulancia
     */
    public function assignDespacho(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'ambulancia_id' => 'required|integer',
            ]);

            Log::info("📥 Asignando despacho {$id} a ambulancia {$validated['ambulancia_id']}");

            // 1. Actualizar despacho en BD
            $despacho = ['id' => $id, 'estado' => 'assigned'];
            $ambulancia = ['id' => $validated['ambulancia_id']];

            // 2. Publicar evento de asignación
            $this->eventPublisher->publishDispatchAssigned(
                (object) $despacho,
                (object) $ambulancia
            );

            Log::info('✅ Evento de asignación publicado');

            return response()->json([
                'dispatch_id' => $id,
                'ambulancia_id' => $validated['ambulancia_id'],
                'estado' => 'assigned',
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Error asignando despacho: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Health check - incluye estado de Redis
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'ms-despacho',
            'redis_connected' => $this->eventPublisher->healthCheck(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
