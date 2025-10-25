<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ambulancia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AmbulanciaController extends Controller
{
    /**
     * Listar ambulancias
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ambulancia::query();

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo_ambulancia')) {
            $query->where('tipo_ambulancia', $request->tipo_ambulancia);
        }

        if ($request->boolean('disponibles')) {
            $query->disponibles();
        }

        $ambulancias = $query->orderBy('placa')->get();

        return response()->json($ambulancias);
    }

    /**
     * Obtener ambulancia por ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $ambulancia = Ambulancia::find($id);

        if (!$ambulancia) {
            return response()->json([
                'error' => 'Ambulancia no encontrada'
            ], 404);
        }

        return response()->json($ambulancia);
    }

    /**
     * Actualizar ubicación de ambulancia
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function actualizarUbicacion(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $ambulancia = Ambulancia::find($id);

        if (!$ambulancia) {
            return response()->json([
                'error' => 'Ambulancia no encontrada'
            ], 404);
        }

        $ambulancia->actualizarUbicacion(
            $request->latitud,
            $request->longitud
        );

        return response()->json([
            'message' => 'Ubicación actualizada exitosamente',
            'data' => $ambulancia
        ]);
    }

    /**
     * Actualizar estado de ambulancia
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:disponible,en_servicio,mantenimiento,fuera_servicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $ambulancia = Ambulancia::find($id);

        if (!$ambulancia) {
            return response()->json([
                'error' => 'Ambulancia no encontrada'
            ], 404);
        }

        $ambulancia->update(['estado' => $request->estado]);

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'data' => $ambulancia
        ]);
    }
}
