<?php

namespace App\GraphQL\Types;

use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class DespachoType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Despacho',
        'description' => 'Despacho de ambulancia',
        'model' => Despacho::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho',
            ],
            'solicitud_id' => [
                'type' => Type::int(),
                'description' => 'ID de la solicitud (MS Recepción)',
            ],
            'ambulancia' => [
                'type' => GraphQL::type('Ambulancia'),
                'description' => 'Ambulancia asignada',
            ],
            'personal_asignado' => [
                'type' => Type::listOf(GraphQL::type('Personal')),
                'description' => 'Personal asignado al despacho',
            ],
            'fecha_solicitud' => [
                'type' => Type::string(),
                'description' => 'Fecha de solicitud',
                'resolve' => function ($root) {
                    return $root->fecha_solicitud?->toIso8601String();
                },
            ],
            'fecha_asignacion' => [
                'type' => Type::string(),
                'description' => 'Fecha de asignación',
                'resolve' => function ($root) {
                    return $root->fecha_asignacion?->toIso8601String();
                },
            ],
            'fecha_llegada' => [
                'type' => Type::string(),
                'description' => 'Fecha de llegada al sitio',
                'resolve' => function ($root) {
                    return $root->fecha_llegada?->toIso8601String();
                },
            ],
            'fecha_finalizacion' => [
                'type' => Type::string(),
                'description' => 'Fecha de finalización',
                'resolve' => function ($root) {
                    return $root->fecha_finalizacion?->toIso8601String();
                },
            ],
            'ubicacion_origen_lat' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Latitud origen',
            ],
            'ubicacion_origen_lng' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Longitud origen',
            ],
            'direccion_origen' => [
                'type' => Type::string(),
                'description' => 'Dirección origen',
            ],
            'ubicacion_destino_lat' => [
                'type' => Type::float(),
                'description' => 'Latitud destino',
            ],
            'ubicacion_destino_lng' => [
                'type' => Type::float(),
                'description' => 'Longitud destino',
            ],
            'direccion_destino' => [
                'type' => Type::string(),
                'description' => 'Dirección destino',
            ],
            'distancia_km' => [
                'type' => Type::float(),
                'description' => 'Distancia en kilómetros',
            ],
            'tiempo_estimado_min' => [
                'type' => Type::int(),
                'description' => 'Tiempo estimado en minutos',
            ],
            'tiempo_real_min' => [
                'type' => Type::int(),
                'description' => 'Tiempo real en minutos',
            ],
            'estado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Estado del despacho',
            ],
            'incidente' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Tipo de incidente',
            ],
            'decision' => [
                'type' => Type::string(),
                'description' => 'Decisión: ambulatoria, traslado',
            ],
            'prioridad' => [
                'type' => Type::string(),
                'description' => 'Prioridad: baja, media, alta, critica',
            ],
            'observaciones' => [
                'type' => Type::string(),
                'description' => 'Observaciones',
            ],
            'datos_adicionales' => [
                'type' => Type::string(),
                'description' => 'Datos adicionales en JSON',
                'resolve' => function ($root) {
                    return $root->datos_adicionales ? json_encode($root->datos_adicionales) : null;
                },
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'Fecha de creación',
                'resolve' => function ($root) {
                    return $root->created_at->toIso8601String();
                },
            ],
        ];
    }
}
