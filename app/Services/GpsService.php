<?php

namespace App\Services;

use Location\Coordinate;
use Location\Distance\Haversine;

class GpsService
{
    /**
     * Calcular distancia entre dos puntos GPS usando fórmula de Haversine
     * 
     * @param float $lat1 Latitud del punto 1
     * @param float $lng1 Longitud del punto 1
     * @param float $lat2 Latitud del punto 2
     * @param float $lng2 Longitud del punto 2
     * @param string $unit Unidad de medida (km, m, mi)
     * @return float Distancia calculada
     */
    public function calcularDistancia(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        string $unit = 'km'
    ): float {
        $coordinate1 = new Coordinate($lat1, $lng1);
        $coordinate2 = new Coordinate($lat2, $lng2);
        
        $calculator = new Haversine();
        $distanceInMeters = $calculator->getDistance($coordinate1, $coordinate2);
        
        return $this->convertirUnidad($distanceInMeters, $unit);
    }

    /**
     * Calcular distancia entre múltiples puntos (ruta)
     * 
     * @param array $puntos Array de puntos [['lat' => x, 'lng' => y], ...]
     * @param string $unit Unidad de medida
     * @return float Distancia total
     */
    public function calcularDistanciaRuta(array $puntos, string $unit = 'km'): float
    {
        if (count($puntos) < 2) {
            return 0.0;
        }

        $distanciaTotal = 0.0;

        for ($i = 0; $i < count($puntos) - 1; $i++) {
            $distanciaTotal += $this->calcularDistancia(
                $puntos[$i]['lat'],
                $puntos[$i]['lng'],
                $puntos[$i + 1]['lat'],
                $puntos[$i + 1]['lng'],
                $unit
            );
        }

        return round($distanciaTotal, 2);
    }

    /**
     * Encontrar el punto más cercano a una ubicación dada
     * 
     * @param float $latOrigen Latitud origen
     * @param float $lngOrigen Longitud origen
     * @param array $puntos Array de puntos con 'id', 'lat', 'lng'
     * @return array|null Punto más cercano con distancia
     */
    public function encontrarPuntoMasCercano(
        float $latOrigen,
        float $lngOrigen,
        array $puntos
    ): ?array {
        if (empty($puntos)) {
            return null;
        }

        $puntoMasCercano = null;
        $distanciaMinima = PHP_FLOAT_MAX;

        foreach ($puntos as $punto) {
            $distancia = $this->calcularDistancia(
                $latOrigen,
                $lngOrigen,
                $punto['lat'],
                $punto['lng']
            );

            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
                $puntoMasCercano = array_merge($punto, [
                    'distancia_km' => round($distancia, 2)
                ]);
            }
        }

        return $puntoMasCercano;
    }

    /**
     * Ordenar puntos por distancia desde un origen
     * 
     * @param float $latOrigen Latitud origen
     * @param float $lngOrigen Longitud origen
     * @param array $puntos Array de puntos
     * @return array Puntos ordenados por distancia
     */
    public function ordenarPorDistancia(
        float $latOrigen,
        float $lngOrigen,
        array $puntos
    ): array {
        $puntosConDistancia = array_map(function ($punto) use ($latOrigen, $lngOrigen) {
            $distancia = $this->calcularDistancia(
                $latOrigen,
                $lngOrigen,
                $punto['lat'],
                $punto['lng']
            );

            return array_merge($punto, [
                'distancia_km' => round($distancia, 2)
            ]);
        }, $puntos);

        usort($puntosConDistancia, function ($a, $b) {
            return $a['distancia_km'] <=> $b['distancia_km'];
        });

        return $puntosConDistancia;
    }

    /**
     * Verificar si un punto está dentro de un radio
     * 
     * @param float $latOrigen Latitud origen
     * @param float $lngOrigen Longitud origen
     * @param float $latDestino Latitud destino
     * @param float $lngDestino Longitud destino
     * @param float $radioKm Radio en kilómetros
     * @return bool
     */
    public function estaDentroDelRadio(
        float $latOrigen,
        float $lngOrigen,
        float $latDestino,
        float $lngDestino,
        float $radioKm
    ): bool {
        $distancia = $this->calcularDistancia($latOrigen, $lngOrigen, $latDestino, $lngDestino);
        return $distancia <= $radioKm;
    }

    /**
     * Calcular punto medio entre dos coordenadas
     * 
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return array ['lat' => x, 'lng' => y]
     */
    public function calcularPuntoMedio(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): array {
        return [
            'lat' => ($lat1 + $lat2) / 2,
            'lng' => ($lng1 + $lng2) / 2,
        ];
    }

    /**
     * Validar coordenadas GPS
     * 
     * @param float $lat Latitud
     * @param float $lng Longitud
     * @return bool
     */
    public function validarCoordenadas(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Convertir metros a la unidad especificada
     * 
     * @param float $metros Distancia en metros
     * @param string $unit Unidad destino (km, m, mi)
     * @return float
     */
    private function convertirUnidad(float $metros, string $unit): float
    {
        return match ($unit) {
            'km' => round($metros / 1000, 2),
            'm' => round($metros, 2),
            'mi' => round($metros / 1609.344, 2), // millas
            default => round($metros / 1000, 2), // por defecto km
        };
    }

    /**
     * Estimar tiempo de viaje basado en distancia y velocidad promedio
     * 
     * @param float $distanciaKm Distancia en kilómetros
     * @param float $velocidadPromedio Velocidad promedio en km/h (default: 40 km/h en ciudad)
     * @return int Tiempo estimado en minutos
     */
    public function estimarTiempoViaje(float $distanciaKm, float $velocidadPromedio = 40): int
    {
        if ($distanciaKm <= 0 || $velocidadPromedio <= 0) {
            return 0;
        }

        $tiempoHoras = $distanciaKm / $velocidadPromedio;
        $tiempoMinutos = $tiempoHoras * 60;

        return (int) ceil($tiempoMinutos);
    }

    /**
     * Obtener información de ubicación formateada
     * 
     * @param float $lat
     * @param float $lng
     * @return array
     */
    public function formatearUbicacion(float $lat, float $lng): array
    {
        return [
            'latitud' => $lat,
            'longitud' => $lng,
            'latitud_dms' => $this->convertirADMS($lat, 'lat'),
            'longitud_dms' => $this->convertirADMS($lng, 'lng'),
        ];
    }

    /**
     * Convertir coordenadas decimales a grados, minutos, segundos (DMS)
     * 
     * @param float $decimal
     * @param string $tipo 'lat' o 'lng'
     * @return string
     */
    private function convertirADMS(float $decimal, string $tipo): string
    {
        $grados = floor(abs($decimal));
        $minutos = floor((abs($decimal) - $grados) * 60);
        $segundos = round(((abs($decimal) - $grados) * 60 - $minutos) * 60, 2);

        $direccion = '';
        if ($tipo === 'lat') {
            $direccion = $decimal >= 0 ? 'N' : 'S';
        } else {
            $direccion = $decimal >= 0 ? 'E' : 'W';
        }

        return "{$grados}° {$minutos}' {$segundos}\" {$direccion}";
    }
}
