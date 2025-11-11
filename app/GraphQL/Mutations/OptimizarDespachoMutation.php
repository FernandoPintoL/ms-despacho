<?php

namespace App\GraphQL\Mutations;

use App\Models\Despacho;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Http;

class OptimizarDespachoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'optimizarDespacho',
        'description' => 'Optimizar asignación de despacho usando ML',
    ];

    public function type(): Type
    {
        return new ObjectType([
            'name' => 'OptimizacionDespacho',
            'fields' => [
                'despacho_id' => [
                    'type' => Type::int(),
                ],
                'ambulancia_sugerida_id' => [
                    'type' => Type::int(),
                ],
                'confianza' => [
                    'type' => Type::float(),
                ],
                'tiempo_estimado_min' => [
                    'type' => Type::int(),
                ],
                'distancia_km' => [
                    'type' => Type::float(),
                ],
                'razon_cambio' => [
                    'type' => Type::string(),
                ],
            ],
        ]);
    }

    public function args(): array
    {
        return [
            'despacho_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho a optimizar',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::with('ambulancia')->findOrFail($args['despacho_id']);

        try {
            // Llamar a servicio ML en ms_ml_despacho
            $mlResponse = Http::timeout(10)->get(
                env('ML_SERVICE_URL', 'http://localhost:5000') . '/api/v1/optimize-dispatch',
                [
                    'despacho_id' => $despacho->id,
                    'ubicacion_origen_lat' => $despacho->ubicacion_origen_lat,
                    'ubicacion_origen_lng' => $despacho->ubicacion_origen_lng,
                    'ubicacion_destino_lat' => $despacho->ubicacion_destino_lat,
                    'ubicacion_destino_lng' => $despacho->ubicacion_destino_lng,
                    'prioridad' => $despacho->prioridad,
                ]
            );

            if ($mlResponse->successful()) {
                $optimization = $mlResponse->json();

                // Opcionalmente actualizar el despacho con la ambulancia sugerida
                if (isset($optimization['ambulancia_id'])) {
                    $despacho->update([
                        'ambulancia_id' => $optimization['ambulancia_id'],
                        'tiempo_estimado_min' => $optimization['tiempo_estimado_min'] ?? null,
                        'distancia_km' => $optimization['distancia_km'] ?? null,
                    ]);
                }

                return [
                    'despacho_id' => $despacho->id,
                    'ambulancia_sugerida_id' => $optimization['ambulancia_id'] ?? null,
                    'confianza' => $optimization['confianza'] ?? 0,
                    'tiempo_estimado_min' => $optimization['tiempo_estimado_min'] ?? null,
                    'distancia_km' => $optimization['distancia_km'] ?? null,
                    'razon_cambio' => $optimization['razon'] ?? 'Optimización automática',
                ];
            }
        } catch (\Exception $e) {
            // Si ML falla, retornar asignación actual
            return [
                'despacho_id' => $despacho->id,
                'ambulancia_sugerida_id' => $despacho->ambulancia_id,
                'confianza' => 0.5,
                'tiempo_estimado_min' => $despacho->tiempo_estimado_min,
                'distancia_km' => $despacho->distancia_km,
                'razon_cambio' => 'Servicio ML no disponible',
            ];
        }
    }
}
