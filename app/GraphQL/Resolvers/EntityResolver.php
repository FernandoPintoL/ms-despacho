<?php

namespace App\GraphQL\Resolvers;

use App\Models\Despacho;
use App\Models\Ambulancia;
use App\Models\Personal;

/**
 * Entity Resolver for Apollo Federation
 *
 * Resolves entity references from other subgraphs.
 * This is required for federation to work properly.
 */
class EntityResolver
{
    /**
     * Resolve Despacho entity reference
     *
     * When another service references a Despacho by ID, this resolver
     * fetches the complete Despacho object from this service.
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return array|null
     */
    public function resolveDespachoReference(array $obj)
    {
        try {
            $despacho = Despacho::with(['ambulancia', 'personalAsignado'])
                ->find($obj['id']);

            if (!$despacho) {
                return null;
            }

            return $this->formatDespachoResponse($despacho);
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving Despacho reference', [
                'despachoId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resolve Ambulancia entity reference
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return array|null
     */
    public function resolveAmbuanciaReference(array $obj)
    {
        try {
            $ambulancia = Ambulancia::find($obj['id']);

            if (!$ambulancia) {
                return null;
            }

            return $this->formatAmbuanciaResponse($ambulancia);
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving Ambulancia reference', [
                'ambulanciaId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resolve Personal entity reference
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return array|null
     */
    public function resolvePersonalReference(array $obj)
    {
        try {
            $personal = Personal::find($obj['id']);

            if (!$personal) {
                return null;
            }

            return $this->formatPersonalResponse($personal);
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving Personal reference', [
                'personalId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format Despacho response with camelCase fields
     */
    private function formatDespachoResponse(Despacho $despacho): array
    {
        return [
            'id' => (string) $despacho->id,
            'solicitudId' => $despacho->solicitud_id ? (string) $despacho->solicitud_id : null,
            'ambulancia' => $despacho->ambulancia ? $this->formatAmbuanciaResponse($despacho->ambulancia) : null,
            'personalAsignado' => $despacho->personalAsignado ? array_map(fn($p) => $this->formatPersonalResponse($p), $despacho->personalAsignado->toArray()) : [],
            'fechaSolicitud' => $despacho->fecha_solicitud ? $despacho->fecha_solicitud->format('Y-m-d H:i:s') : null,
            'fechaAsignacion' => $despacho->fecha_asignacion ? $despacho->fecha_asignacion->format('Y-m-d H:i:s') : null,
            'fechaLlegada' => $despacho->fecha_llegada ? $despacho->fecha_llegada->format('Y-m-d H:i:s') : null,
            'fechaFinalizacion' => $despacho->fecha_finalizacion ? $despacho->fecha_finalizacion->format('Y-m-d H:i:s') : null,
            'ubicacionOrigenLat' => (float) $despacho->ubicacion_origen_lat,
            'ubicacionOrigenLng' => (float) $despacho->ubicacion_origen_lng,
            'direccionOrigen' => $despacho->direccion_origen,
            'ubicacionDestinoLat' => $despacho->ubicacion_destino_lat ? (float) $despacho->ubicacion_destino_lat : null,
            'ubicacionDestinoLng' => $despacho->ubicacion_destino_lng ? (float) $despacho->ubicacion_destino_lng : null,
            'direccionDestino' => $despacho->direccion_destino,
            'distanciaKm' => $despacho->distancia_km ? (float) $despacho->distancia_km : null,
            'tiempoEstimadoMin' => $despacho->tiempo_estimado_min,
            'tiempoRealMin' => $despacho->tiempo_real_min,
            'estado' => $despacho->estado,
            'incidente' => $despacho->incidente,
            'decision' => $despacho->decision,
            'prioridad' => $despacho->prioridad,
            'observaciones' => $despacho->observaciones,
            'datosAdicionales' => $despacho->datos_adicionales ? json_encode($despacho->datos_adicionales) : null,
            'createdAt' => $despacho->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format Ambulancia response with camelCase fields
     */
    private function formatAmbuanciaResponse(Ambulancia $ambulancia): array
    {
        return [
            'id' => (string) $ambulancia->id,
            'placa' => $ambulancia->placa,
            'modelo' => $ambulancia->modelo,
            'tipoAmbulancia' => $ambulancia->tipo_ambulancia,
            'estado' => $ambulancia->estado,
            'caracteristicas' => $ambulancia->caracteristicas ? json_encode($ambulancia->caracteristicas) : null,
            'ubicacionActualLat' => $ambulancia->ubicacion_actual_lat ? (float) $ambulancia->ubicacion_actual_lat : null,
            'ubicacionActualLng' => $ambulancia->ubicacion_actual_lng ? (float) $ambulancia->ubicacion_actual_lng : null,
            'ultimaActualizacion' => $ambulancia->ultima_actualizacion ? $ambulancia->ultima_actualizacion->format('Y-m-d H:i:s') : null,
            'createdAt' => $ambulancia->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $ambulancia->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format Personal response with camelCase fields
     */
    private function formatPersonalResponse(Personal $personal): array
    {
        return [
            'id' => (string) $personal->id,
            'nombre' => $personal->nombre,
            'apellido' => $personal->apellido,
            'nombreCompleto' => $personal->nombre_completo,
            'ci' => $personal->ci,
            'rol' => $personal->rol,
            'especialidad' => $personal->especialidad,
            'experiencia' => $personal->experiencia,
            'estado' => $personal->estado,
            'telefono' => $personal->telefono,
            'email' => $personal->email,
            'createdAt' => $personal->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
