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
        'description' => 'Despacho de ambulancia - Apollo Federation Subgraph Entity',
        'model' => Despacho::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),  // Changed from Type::int() to Type::id() for Federation
                'description' => 'ID del despacho (Federation Key)',
            ],
            'solicitudId' => [
                'type' => Type::id(),
                'description' => 'ID de la solicitud (MS Recepción)',
                'resolve' => function ($root) {
                    return $root->solicitud_id ? (string)$root->solicitud_id : null;
                },
            ],
            'ambulancia' => [
                'type' => GraphQL::type('Ambulancia'),
                'description' => 'Ambulancia asignada',
            ],
            'personalAsignado' => [
                'type' => Type::listOf(GraphQL::type('Personal')),
                'description' => 'Personal asignado al despacho',
                'resolve' => function ($root) {
                    return $root->personal_asignado ?? $root->personalAsignado;
                },
            ],
            'fechaSolicitud' => [
                'type' => Type::string(),
                'description' => 'Fecha de solicitud',
                'resolve' => function ($root) {
                    return $root->fecha_solicitud ? $root->fecha_solicitud->format('Y-m-d H:i:s') : null;
                },
            ],
            'fechaAsignacion' => [
                'type' => Type::string(),
                'description' => 'Fecha de asignación',
                'resolve' => function ($root) {
                    return $root->fecha_asignacion ? $root->fecha_asignacion->format('Y-m-d H:i:s') : null;
                },
            ],
            'fechaLlegada' => [
                'type' => Type::string(),
                'description' => 'Fecha de llegada al sitio',
                'resolve' => function ($root) {
                    return $root->fecha_llegada ? $root->fecha_llegada->format('Y-m-d H:i:s') : null;
                },
            ],
            'fechaFinalizacion' => [
                'type' => Type::string(),
                'description' => 'Fecha de finalización',
                'resolve' => function ($root) {
                    return $root->fecha_finalizacion ? $root->fecha_finalizacion->format('Y-m-d H:i:s') : null;
                },
            ],
            'ubicacionOrigenLat' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Latitud origen',
                'resolve' => function ($root) {
                    return $root->ubicacion_origen_lat;
                },
            ],
            'ubicacionOrigenLng' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Longitud origen',
                'resolve' => function ($root) {
                    return $root->ubicacion_origen_lng;
                },
            ],
            'direccionOrigen' => [
                'type' => Type::string(),
                'description' => 'Dirección origen',
                'resolve' => function ($root) {
                    return $root->direccion_origen;
                },
            ],
            'ubicacionDestinoLat' => [
                'type' => Type::float(),
                'description' => 'Latitud destino',
                'resolve' => function ($root) {
                    return $root->ubicacion_destino_lat;
                },
            ],
            'ubicacionDestinoLng' => [
                'type' => Type::float(),
                'description' => 'Longitud destino',
                'resolve' => function ($root) {
                    return $root->ubicacion_destino_lng;
                },
            ],
            'direccionDestino' => [
                'type' => Type::string(),
                'description' => 'Dirección destino',
                'resolve' => function ($root) {
                    return $root->direccion_destino;
                },
            ],
            'distanciaKm' => [
                'type' => Type::float(),
                'description' => 'Distancia en kilómetros',
                'resolve' => function ($root) {
                    return $root->distancia_km;
                },
            ],
            'tiempoEstimadoMin' => [
                'type' => Type::int(),
                'description' => 'Tiempo estimado en minutos',
                'resolve' => function ($root) {
                    return $root->tiempo_estimado_min;
                },
            ],
            'tiempoRealMin' => [
                'type' => Type::int(),
                'description' => 'Tiempo real en minutos',
                'resolve' => function ($root) {
                    return $root->tiempo_real_min;
                },
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
            'datosAdicionales' => [
                'type' => Type::string(),
                'description' => 'Datos adicionales en JSON',
                'resolve' => function ($root) {
                    return $root->datos_adicionales ? json_encode($root->datos_adicionales) : null;
                },
            ],
            'createdAt' => [
                'type' => Type::string(),
                'description' => 'Fecha de creación',
                'resolve' => function ($root) {
                    return $root->created_at->format('Y-m-d H:i:s');
                },
            ],
        ];
    }
}
