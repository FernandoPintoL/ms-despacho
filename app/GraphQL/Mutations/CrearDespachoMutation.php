<?php

namespace App\GraphQL\Mutations;

use App\Services\AsignacionService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CrearDespachoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'crearDespacho',
        'description' => 'Crear un nuevo despacho con asignación automática',
    ];

    public function __construct(
        private AsignacionService $asignacionService
    ) {}

    public function type(): Type
    {
        return GraphQL::type('Despacho');
    }

    public function args(): array
    {
        return [
            'solicitud_id' => [
                'type' => Type::int(),
                'description' => 'ID de la solicitud (MS Recepción)',
            ],
            'ubicacion_origen_lat' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Latitud del origen',
            ],
            'ubicacion_origen_lng' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Longitud del origen',
            ],
            'direccion_origen' => [
                'type' => Type::string(),
                'description' => 'Dirección del origen',
            ],
            'ubicacion_destino_lat' => [
                'type' => Type::float(),
                'description' => 'Latitud del destino',
            ],
            'ubicacion_destino_lng' => [
                'type' => Type::float(),
                'description' => 'Longitud del destino',
            ],
            'direccion_destino' => [
                'type' => Type::string(),
                'description' => 'Dirección del destino',
            ],
            'incidente' => [
                'type' => Type::string(),
                'description' => 'Tipo de incidente',
                'defaultValue' => 'emergencia_medica',
            ],
            'prioridad' => [
                'type' => Type::string(),
                'description' => 'Prioridad: baja, media, alta, critica',
                'defaultValue' => 'media',
            ],
            'tipo_ambulancia' => [
                'type' => Type::string(),
                'description' => 'Tipo de ambulancia requerido',
            ],
            'observaciones' => [
                'type' => Type::string(),
                'description' => 'Observaciones adicionales',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = $this->asignacionService->crearDespacho($args);

        if (!$despacho) {
            throw new \Exception('No se pudo crear el despacho. No hay recursos disponibles.');
        }

        return $despacho;
    }
}
