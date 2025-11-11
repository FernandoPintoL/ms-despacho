<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ServiceQuery extends Query
{
    protected $attributes = [
        'name' => '_service',
        'description' => 'Apollo Federation Service Query - exposes schema SDL for composition',
    ];

    public function type(): Type
    {
        return GraphQL::type('_Service');
    }

    public function resolve($root, $args)
    {
        // Return the SDL schema for Apollo Federation
        $sdl = $this->generateSDL();

        return [
            'sdl' => $sdl,
        ];
    }

    /**
     * Generate the SDL schema for Apollo Federation
     * This schema must be a valid Apollo Federation v2 schema
     */
    private function generateSDL(): string
    {
        return <<<'SDL'
extend schema
  @link(url: "https://specs.apollo.dev/federation/v2.0", import: ["@key"])

type Query {
  ambulancia(id: ID!): Ambulancia
  ambulancias: [Ambulancia!]!
  despacho(id: ID!): Despacho
  despachos: [Despacho!]!
  despachosRecientes: [Despacho!]!
  estadisticasDespachos: EstadisticasDespachos
  personal(id: ID!): Personal
  personales: [Personal!]!
}

type Mutation {
  crearDespacho(input: CrearDespachoInput!): Despacho
  actualizarEstadoDespacho(id: ID!, estado: String!): Despacho
  actualizarUbicacionAmbulancia(id: ID!, lat: Float!, lng: Float!): Ambulancia
  registrarUbicacionGPS(despachId: ID!, ubicacionLat: Float!, ubicacionLng: Float!): Despacho
  agregarFeedbackDespacho(despachId: ID!, resultadoPaciente: String!): FeedbackResponse
  optimizarDespacho(despachId: ID!): OptimizacionDespacho
  crearPersonal(input: CrearPersonalInput!): Personal
  actualizarPersonal(id: ID!, input: ActualizarPersonalInput!): Personal
  cambiarEstadoPersonal(id: ID!, estado: String!): Personal
  asignarPersonal(despachId: ID!, personalId: ID!): Despacho
  desasignarPersonal(despachId: ID!, personalId: ID!): Despacho
}

type Despacho @key(fields: "id") {
  id: ID!
  solicitudId: ID
  ambulancia: Ambulancia
  personalAsignado: [Personal!]!
  fechaSolicitud: String
  fechaAsignacion: String
  fechaLlegada: String
  fechaFinalizacion: String
  ubicacionOrigenLat: Float!
  ubicacionOrigenLng: Float!
  direccionOrigen: String!
  ubicacionDestinoLat: Float
  ubicacionDestinoLng: Float
  direccionDestino: String
  distanciaKm: Float
  tiempoEstimadoMin: Int
  tiempoRealMin: Int
  estado: String!
  incidente: String
  decision: String
  prioridad: String
  observaciones: String
  datosAdicionales: String
  createdAt: String!
}

type Ambulancia @key(fields: "id") {
  id: ID!
  placa: String!
  modelo: String
  tipoAmbulancia: String!
  estado: String!
  caracteristicas: String
  ubicacionActualLat: Float
  ubicacionActualLng: Float
  ultimaActualizacion: String
  createdAt: String!
  updatedAt: String!
}

type Personal @key(fields: "id") {
  id: ID!
  nombre: String!
  apellido: String
  nombreCompleto: String!
  ci: String!
  rol: String!
  especialidad: String
  experiencia: Int
  estado: String!
  telefono: String
  email: String
  createdAt: String!
}

type EstadisticasDespachos {
  totalDespachos: Int!
  enCamino: Int!
  enSitio: Int!
  finalizados: Int!
  tasaCompletcion: Float!
  tiempoPromedio: Float
}

type FeedbackResponse {
  success: Boolean!
  message: String
}

type OptimizacionDespacho {
  despachoId: ID!
  ambulanciaRecomendada: Ambulancia
  personalRecomendado: [Personal!]!
  rutas: [String!]!
  tiempoEstimado: Int
}

input CrearDespachoInput {
  solicitudId: ID
  ambulanciaId: ID!
  ubicacionOrigenLat: Float!
  ubicacionOrigenLng: Float!
  direccionOrigen: String!
  ubicacionDestinoLat: Float
  ubicacionDestinoLng: Float
  direccionDestino: String
  prioridad: String
  incidente: String
}

input ActualizarPersonalInput {
  nombre: String
  apellido: String
  ci: String
  rol: String
  especialidad: String
  experiencia: Int
  estado: String
  telefono: String
  email: String
}

input CrearPersonalInput {
  nombre: String!
  apellido: String
  nombreCompleto: String!
  ci: String!
  rol: String!
  especialidad: String
  experiencia: Int
  estado: String
  telefono: String
  email: String
}

type _Service {
  sdl: String!
}
SDL;
    }
}
