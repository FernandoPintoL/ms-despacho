<?php

namespace App\GraphQL\Types;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PersonalType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Personal',
        'description' => 'Personal médico del sistema - Apollo Federation Subgraph Entity',
        'model' => Personal::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),  // Changed from Type::int() for Federation
                'description' => 'ID del personal (Federation Key)',
            ],
            'nombre' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Nombre',
            ],
            'apellido' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Apellido',
            ],
            'nombreCompleto' => [
                'type' => Type::string(),
                'description' => 'Nombre completo',
                'resolve' => function ($root) {
                    return $root->nombre_completo;
                },
            ],
            'ci' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Cédula de identidad',
            ],
            'rol' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Rol: paramedico, conductor, medico, enfermero',
            ],
            'especialidad' => [
                'type' => Type::string(),
                'description' => 'Especialidad médica',
            ],
            'experiencia' => [
                'type' => Type::int(),
                'description' => 'Años de experiencia',
            ],
            'estado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Estado: disponible, en_servicio, descanso, vacaciones',
            ],
            'telefono' => [
                'type' => Type::string(),
                'description' => 'Teléfono de contacto',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'Email',
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
