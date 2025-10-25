<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Despacho;
use App\Services\AsignacionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DespachoController extends Controller
{
    public function __construct(
        private AsignacionService $asignacionService
    ) {}

    /**
     * Listar despachos
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Despacho::with(['ambulancia', 'personalAsignado']);

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->boolean('activos')) {
            $query->activos();
        }

        $despachos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($despachos);
    }

    /**
     * Crear nuevo despacho
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'solicitud_id' => 'nullable|integer',
            'ubicacion_origen_lat' => 'required|numeric|between:-90,90',
            'ubicacion_origen_lng' => 'required|numeric|between:-180,180',
            'direccion_origen' => 'nullable|string|max:255',
            'ubicacion_destino_lat' => 'nullable|numeric|between:-90,90',
            'ubicacion_destino_lng' => 'nullable|numeric|between:-180,180',
            'direccion_destino' => 'nullable|string|max:255',
            'incidente' => 'nullable|in:accidente,emergencia_medica,traslado,otro',
            'prioridad' => 'nullable|in:baja,media,alta,critica',
            'tipo_ambulancia' => 'nullable|in:basica,intermedia,avanzada,uci',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $despacho = $this->asignacionService->crearDespacho($request->all());

            if (!$despacho) {
                return response()->json([
                    'error' => 'No se pudo crear el despacho',
                    'message' => 'No hay recursos disponibles'
                ], 503);
            }

            return response()->json([
                'message' => 'Despacho creado exitosamente',
                'data' => $despacho->load(['ambulancia', 'personalAsignado'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear despacho',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener despacho por ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $despacho = Despacho::with(['ambulancia', 'personalAsignado', 'historialRastreo'])
            ->find($id);

        if (!$despacho) {
            return response()->json([
                'error' => 'Despacho no encontrado'
            ], 404);
        }

        return response()->json($despacho);
    }

    /**
     * Actualizar estado del despacho
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:pendiente,asignado,en_camino,en_sitio,trasladando,completado,cancelado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $despacho = Despacho::find($id);

        if (!$despacho) {
            return response()->json([
                'error' => 'Despacho no encontrado'
            ], 404);
        }

        $despacho->cambiarEstado($request->estado);

        // Actualizar fechas según el estado
        switch ($request->estado) {
            case 'en_camino':
                if (!$despacho->fecha_asignacion) {
                    $despacho->fecha_asignacion = now();
                }
                break;
            case 'en_sitio':
                if (!$despacho->fecha_llegada) {
                    $despacho->fecha_llegada = now();
                }
                break;
            case 'completado':
            case 'cancelado':
                if (!$despacho->fecha_finalizacion) {
                    $despacho->fecha_finalizacion = now();
                }
                // Finalizar y liberar recursos
                $this->asignacionService->finalizarDespacho($despacho, $request->estado);
                break;
        }

        $despacho->save();

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'data' => $despacho->load(['ambulancia', 'personalAsignado'])
        ]);
    }

    /**
     * Registrar rastreo GPS
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rastreo(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'velocidad' => 'nullable|numeric|min:0',
            'altitud' => 'nullable|numeric',
            'precision' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $despacho = Despacho::find($id);

        if (!$despacho) {
            return response()->json([
                'error' => 'Despacho no encontrado'
            ], 404);
        }

        // Registrar en historial de rastreo
        $rastreo = $despacho->historialRastreo()->create([
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'velocidad' => $request->velocidad,
            'altitud' => $request->altitud,
            'precision' => $request->precision,
            'timestamp_gps' => now(),
        ]);

        // Actualizar ubicación de la ambulancia
        if ($despacho->ambulancia) {
            $despacho->ambulancia->actualizarUbicacion(
                $request->latitud,
                $request->longitud
            );
        }

        return response()->json([
            'message' => 'Ubicación registrada exitosamente',
            'data' => $rastreo
        ], 201);
    }
}
