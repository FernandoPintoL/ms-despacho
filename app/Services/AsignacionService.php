<?php

namespace App\Services;

use App\Models\Ambulancia;
use App\Models\Personal;
use App\Models\Despacho;
use App\Events\DespachoCreado;
use App\Events\DespachoEstadoCambiado;
use App\Events\DespachoFinalizado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsignacionService
{
    public function __construct(
        private GpsService $gpsService
    ) {}

    /**
     * Encontrar la ambulancia más cercana y disponible
     * 
     * @param float $latOrigen Latitud del incidente
     * @param float $lngOrigen Longitud del incidente
     * @param string|null $tipoRequerido Tipo de ambulancia requerido
     * @param float $radioMaximoKm Radio máximo de búsqueda
     * @return array|null ['ambulancia' => Ambulancia, 'distancia_km' => float]
     */
    public function encontrarAmbulanciaMasCercana(
        float $latOrigen,
        float $lngOrigen,
        ?string $tipoRequerido = null,
        float $radioMaximoKm = 50
    ): ?array {
        // Validar coordenadas
        if (!$this->gpsService->validarCoordenadas($latOrigen, $lngOrigen)) {
            Log::warning('Coordenadas inválidas', ['lat' => $latOrigen, 'lng' => $lngOrigen]);
            return null;
        }

        // Obtener ambulancias disponibles
        $query = Ambulancia::disponibles()
            ->whereNotNull('ubicacion_actual_lat')
            ->whereNotNull('ubicacion_actual_lng');

        // Filtrar por tipo si se especifica
        if ($tipoRequerido) {
            $query->where('tipo_ambulancia', $tipoRequerido);
        }

        $ambulancias = $query->get();

        if ($ambulancias->isEmpty()) {
            Log::info('No hay ambulancias disponibles', ['tipo' => $tipoRequerido]);
            return null;
        }

        // Convertir a array para el GPS service
        $puntosAmbulancias = $ambulancias->map(function ($ambulancia) {
            return [
                'id' => $ambulancia->id,
                'lat' => (float) $ambulancia->ubicacion_actual_lat,
                'lng' => (float) $ambulancia->ubicacion_actual_lng,
                'ambulancia' => $ambulancia,
            ];
        })->toArray();

        // Encontrar la más cercana
        $resultado = $this->gpsService->encontrarPuntoMasCercano(
            $latOrigen,
            $lngOrigen,
            $puntosAmbulancias
        );

        if (!$resultado) {
            return null;
        }

        // Verificar que esté dentro del radio máximo
        if ($resultado['distancia_km'] > $radioMaximoKm) {
            Log::warning('Ambulancia más cercana fuera del radio', [
                'distancia' => $resultado['distancia_km'],
                'radio_maximo' => $radioMaximoKm
            ]);
            return null;
        }

        return [
            'ambulancia' => $resultado['ambulancia'],
            'distancia_km' => $resultado['distancia_km'],
        ];
    }

    /**
     * Obtener lista de ambulancias ordenadas por distancia
     * 
     * @param float $latOrigen
     * @param float $lngOrigen
     * @param int $limite Número máximo de resultados
     * @return Collection
     */
    public function listarAmbulanciasPorDistancia(
        float $latOrigen,
        float $lngOrigen,
        int $limite = 5
    ): Collection {
        $ambulancias = Ambulancia::disponibles()
            ->whereNotNull('ubicacion_actual_lat')
            ->whereNotNull('ubicacion_actual_lng')
            ->get();

        if ($ambulancias->isEmpty()) {
            return collect([]);
        }

        $puntosAmbulancias = $ambulancias->map(function ($ambulancia) {
            return [
                'id' => $ambulancia->id,
                'lat' => (float) $ambulancia->ubicacion_actual_lat,
                'lng' => (float) $ambulancia->ubicacion_actual_lng,
                'ambulancia' => $ambulancia,
            ];
        })->toArray();

        $ordenadas = $this->gpsService->ordenarPorDistancia(
            $latOrigen,
            $lngOrigen,
            $puntosAmbulancias
        );

        return collect($ordenadas)
            ->take($limite)
            ->map(function ($item) {
                return [
                    'ambulancia' => $item['ambulancia'],
                    'distancia_km' => $item['distancia_km'],
                ];
            });
    }

    /**
     * Asignar personal disponible a un despacho
     * 
     * @param Despacho $despacho
     * @param array $rolesRequeridos ['paramedico' => 1, 'conductor' => 1]
     * @return array Personal asignado
     */
    public function asignarPersonal(Despacho $despacho, array $rolesRequeridos = []): array
    {
        // Roles por defecto si no se especifican
        if (empty($rolesRequeridos)) {
            $rolesRequeridos = [
                'conductor' => 1,
                'paramedico' => 1,
            ];
        }

        $personalAsignado = [];

        DB::beginTransaction();
        try {
            foreach ($rolesRequeridos as $rol => $cantidad) {
                $personal = Personal::disponibles()
                    ->where('rol', $rol)
                    ->limit($cantidad)
                    ->get();

                if ($personal->count() < $cantidad) {
                    Log::warning("No hay suficiente personal disponible", [
                        'rol' => $rol,
                        'requerido' => $cantidad,
                        'disponible' => $personal->count()
                    ]);
                    
                    DB::rollBack();
                    return [];
                }

                foreach ($personal as $index => $persona) {
                    // Asignar a despacho
                    $despacho->personalAsignado()->attach($persona->id, [
                        'rol_asignado' => $rol,
                        'es_responsable' => $index === 0, // El primero es responsable
                    ]);

                    // Marcar como en servicio
                    $persona->marcarEnServicio();

                    $personalAsignado[] = [
                        'personal' => $persona,
                        'rol_asignado' => $rol,
                        'es_responsable' => $index === 0,
                    ];
                }
            }

            DB::commit();
            
            Log::info('Personal asignado exitosamente', [
                'despacho_id' => $despacho->id,
                'cantidad' => count($personalAsignado)
            ]);

            return $personalAsignado;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al asignar personal', [
                'despacho_id' => $despacho->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Liberar personal de un despacho
     * 
     * @param Despacho $despacho
     * @return void
     */
    public function liberarPersonal(Despacho $despacho): void
    {
        DB::beginTransaction();
        try {
            $personal = $despacho->personalAsignado;

            foreach ($personal as $persona) {
                $persona->marcarDisponible();
            }

            $despacho->personalAsignado()->detach();

            DB::commit();

            Log::info('Personal liberado', [
                'despacho_id' => $despacho->id,
                'cantidad' => $personal->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al liberar personal', [
                'despacho_id' => $despacho->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crear despacho completo con ambulancia y personal
     * 
     * @param array $datos Datos del despacho
     * @return Despacho|null
     */
    public function crearDespacho(array $datos): ?Despacho
    {
        DB::beginTransaction();
        try {
            // 1. Encontrar ambulancia más cercana
            $resultadoAmbulancia = $this->encontrarAmbulanciaMasCercana(
                $datos['ubicacion_origen_lat'],
                $datos['ubicacion_origen_lng'],
                $datos['tipo_ambulancia'] ?? null
            );

            if (!$resultadoAmbulancia) {
                Log::warning('No se encontró ambulancia disponible');
                DB::rollBack();
                return null;
            }

            $ambulancia = $resultadoAmbulancia['ambulancia'];
            $distanciaKm = $resultadoAmbulancia['distancia_km'];

            // 2. Estimar tiempo de viaje
            $tiempoEstimadoMin = $this->gpsService->estimarTiempoViaje($distanciaKm);

            // 3. Crear despacho
            $despacho = Despacho::create([
                'solicitud_id' => $datos['solicitud_id'] ?? null,
                'ambulancia_id' => $ambulancia->id,
                'fecha_solicitud' => now(),
                'fecha_asignacion' => now(),
                'ubicacion_origen_lat' => $datos['ubicacion_origen_lat'],
                'ubicacion_origen_lng' => $datos['ubicacion_origen_lng'],
                'direccion_origen' => $datos['direccion_origen'] ?? null,
                'ubicacion_destino_lat' => $datos['ubicacion_destino_lat'] ?? null,
                'ubicacion_destino_lng' => $datos['ubicacion_destino_lng'] ?? null,
                'direccion_destino' => $datos['direccion_destino'] ?? null,
                'distancia_km' => $distanciaKm,
                'tiempo_estimado_min' => $tiempoEstimadoMin,
                'estado' => 'asignado',
                'incidente' => $datos['incidente'] ?? 'emergencia_medica',
                'prioridad' => $datos['prioridad'] ?? 'media',
                'observaciones' => $datos['observaciones'] ?? null,
                'datos_adicionales' => $datos['datos_adicionales'] ?? null,
            ]);

            // 4. Marcar ambulancia como en servicio
            $ambulancia->marcarEnServicio();

            // 5. Asignar personal
            $rolesRequeridos = $datos['roles_requeridos'] ?? [
                'conductor' => 1,
                'paramedico' => 1,
            ];

            $personalAsignado = $this->asignarPersonal($despacho, $rolesRequeridos);

            if (empty($personalAsignado)) {
                Log::warning('No se pudo asignar personal');
                DB::rollBack();
                return null;
            }

            DB::commit();

            Log::info('Despacho creado exitosamente', [
                'despacho_id' => $despacho->id,
                'ambulancia_id' => $ambulancia->id,
                'distancia_km' => $distanciaKm,
                'tiempo_estimado_min' => $tiempoEstimadoMin
            ]);

            // Recargar relaciones
            $despacho->load(['ambulancia', 'personalAsignado']);

            // Disparar evento
            event(new DespachoCreado($despacho));

            return $despacho;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear despacho', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Finalizar despacho y liberar recursos
     * 
     * @param Despacho $despacho
     * @param string $resultado 'completado', 'cancelado', etc.
     * @return bool
     */
    public function finalizarDespacho(Despacho $despacho, string $resultado = 'completado'): bool
    {
        DB::beginTransaction();
        try {
            // Calcular tiempo real
            if ($despacho->fecha_asignacion) {
                $tiempoReal = $despacho->calcularTiempoReal();
                $despacho->tiempo_real_min = $tiempoReal;
            }

            // Actualizar estado
            $despacho->estado = $resultado;
            $despacho->fecha_finalizacion = now();
            $despacho->save();

            // Liberar ambulancia
            if ($despacho->ambulancia) {
                $despacho->ambulancia->marcarDisponible();
            }

            // Liberar personal
            $this->liberarPersonal($despacho);

            DB::commit();

            Log::info('Despacho finalizado', [
                'despacho_id' => $despacho->id,
                'resultado' => $resultado,
                'tiempo_real_min' => $despacho->tiempo_real_min
            ]);

            // Disparar evento
            event(new DespachoFinalizado($despacho, $resultado));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al finalizar despacho', [
                'despacho_id' => $despacho->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener estadísticas de disponibilidad
     * 
     * @return array
     */
    public function obtenerEstadisticasDisponibilidad(): array
    {
        return [
            'ambulancias' => [
                'total' => Ambulancia::count(),
                'disponibles' => Ambulancia::disponibles()->count(),
                'en_servicio' => Ambulancia::enServicio()->count(),
                'por_tipo' => Ambulancia::selectRaw('tipo_ambulancia, COUNT(*) as total')
                    ->groupBy('tipo_ambulancia')
                    ->pluck('total', 'tipo_ambulancia')
                    ->toArray(),
            ],
            'personal' => [
                'total' => Personal::count(),
                'disponibles' => Personal::disponibles()->count(),
                'en_servicio' => Personal::where('estado', 'en_servicio')->count(),
                'por_rol' => Personal::selectRaw('rol, COUNT(*) as total')
                    ->groupBy('rol')
                    ->pluck('total', 'rol')
                    ->toArray(),
            ],
            'despachos_activos' => Despacho::activos()->count(),
        ];
    }
}
