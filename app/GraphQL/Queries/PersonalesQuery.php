<?php

namespace App\GraphQL\Queries;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PersonalesQuery extends Query
{
    protected $attributes = [
        'name' => 'personales',
        'description' => 'Listar personal con filtros opcionales',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Personal'));
    }

    public function args(): array
    {
        return [
            'rol' => [
                'type' => Type::string(),
                'description' => 'Filtrar por rol (paramedico, conductor, medico, enfermero)',
            ],
            'estado' => [
                'type' => Type::string(),
                'description' => 'Filtrar por estado (disponible, en_servicio, descanso, vacaciones)',
            ],
            'disponibles' => [
                'type' => Type::boolean(),
                'description' => 'Solo personal disponible',
            ],
            'limit' => [
                'type' => Type::int(),
                'description' => 'Número máximo de resultados',
                'defaultValue' => 50,
            ],
            'offset' => [
                'type' => Type::int(),
                'description' => 'Desplazamiento para paginación',
                'defaultValue' => 0,
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $query = Personal::query();

        if (isset($args['rol'])) {
            $query->porRol($args['rol']);
        }

        if (isset($args['estado'])) {
            $query->where('estado', $args['estado']);
        }

        if (isset($args['disponibles']) && $args['disponibles']) {
            $query->disponibles();
        }

        $query->orderBy('nombre', 'asc');

        return $query->offset($args['offset'] ?? 0)
            ->limit($args['limit'] ?? 50)
            ->get();
    }
}
