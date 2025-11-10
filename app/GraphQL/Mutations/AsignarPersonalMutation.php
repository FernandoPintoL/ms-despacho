<?php

namespace App\GraphQL\Mutations;

use App\Models\AsignacionPersonal;
use App\Models\Personal;
use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class AsignarPersonalMutation extends Mutation
{
    protected $attributes = [
        'name' => 'asignarPersonal',
        'description' => 'Asignar personal a un despacho',
    ];

    public function type(): Type
    {
        return GraphQL::type('Despacho');
    }

    public function args(): array
    {
        return [
            'despacho_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho',
            ],
            'personal_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del personal',
            ],
            'rol_asignado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Rol asignado: paramedico, conductor, medico, enfermero',
            ],
            'es_responsable' => [
                'type' => Type::boolean(),
                'description' => 'Indicar si es responsable del despacho',
                'defaultValue' => false,
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::findOrFail($args['despacho_id']);
        $personal = Personal::findOrFail($args['personal_id']);

        // Validar que la asignación no exista
        if ($despacho->personalAsignado()->where('personal_id', $args['personal_id'])->exists()) {
            throw new \Exception('Este personal ya está asignado a este despacho');
        }

        // Crear asignación
        AsignacionPersonal::create([
            'despacho_id' => $args['despacho_id'],
            'personal_id' => $args['personal_id'],
            'rol_asignado' => $args['rol_asignado'],
            'es_responsable' => $args['es_responsable'] ?? false,
        ]);

        // Marcar personal como en servicio
        $personal->marcarEnServicio();

        // Cargar relaciones y retornar despacho actualizado
        $despacho->load(['ambulancia', 'personalAsignado']);

        return $despacho;
    }
}
