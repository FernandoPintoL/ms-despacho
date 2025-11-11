<?php

namespace App\GraphQL\Queries;

use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class DespachosRecientesQuery extends Query
{
    protected $attributes = [
        'name' => 'despachosRecientes',
        'description' => 'Obtener despachos recientes de las últimas N horas',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Despacho'));
    }

    public function args(): array
    {
        return [
            'horas' => [
                'type' => Type::int(),
                'description' => 'Número de horas hacia atrás',
                'defaultValue' => 24,
            ],
            'limit' => [
                'type' => Type::int(),
                'description' => 'Límite de resultados',
                'defaultValue' => 50,
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $horas = $args['horas'] ?? 24;
        $limit = $args['limit'] ?? 50;

        $fechaDesde = now()->subHours($horas);

        return Despacho::with(['ambulancia', 'personalAsignado'])
            ->where('created_at', '>=', $fechaDesde)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
