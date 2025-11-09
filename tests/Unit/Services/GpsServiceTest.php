<?php

namespace Tests\Unit\Services;

use App\Services\GpsService;
use PHPUnit\Framework\TestCase;

class GpsServiceTest extends TestCase
{
    private GpsService $gpsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gpsService = new GpsService();
    }

    /**
     * Test Haversine formula distance calculation
     * La Paz (Bolivia): -16.5, -68.15
     * Cochabamba (Bolivia): -17.3895, -66.1568
     * Expected distance: ~300 km
     */
    public function test_calcular_distancia_entre_dos_puntos(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;
        $latDestino = -17.3895;
        $lngDestino = -66.1568;

        $distancia = $this->gpsService->calcularDistancia(
            $latOrigen,
            $lngOrigen,
            $latDestino,
            $lngDestino,
            'km'
        );

        $this->assertGreaterThan(290, $distancia);
        $this->assertLessThan(310, $distancia);
    }

    /**
     * Test distance is 0 for same coordinates
     */
    public function test_calcular_distancia_punto_mismo(): void
    {
        $lat = -16.5;
        $lng = -68.15;

        $distancia = $this->gpsService->calcularDistancia($lat, $lng, $lat, $lng);

        $this->assertEquals(0, $distancia);
    }

    /**
     * Test distance calculation with different units
     */
    public function test_calcular_distancia_diferentes_unidades(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;
        $latDestino = -17.3895;
        $lngDestino = -66.1568;

        $distanciaKm = $this->gpsService->calcularDistancia(
            $latOrigen,
            $lngOrigen,
            $latDestino,
            $lngDestino,
            'km'
        );

        $distanciaMillas = $this->gpsService->calcularDistancia(
            $latOrigen,
            $lngOrigen,
            $latDestino,
            $lngDestino,
            'mi'
        );

        // 1 km = 0.621371 miles
        $this->assertAlmostEqual(
            $distanciaKm * 0.621371,
            $distanciaMillas,
            2
        );
    }

    /**
     * Test validation of valid coordinates
     */
    public function test_validar_coordenadas_validas(): void
    {
        $this->assertTrue($this->gpsService->validarCoordenadas(-16.5, -68.15));
        $this->assertTrue($this->gpsService->validarCoordenadas(0, 0));
        $this->assertTrue($this->gpsService->validarCoordenadas(90, 180));
        $this->assertTrue($this->gpsService->validarCoordenadas(-90, -180));
    }

    /**
     * Test validation of invalid coordinates
     */
    public function test_validar_coordenadas_invalidas(): void
    {
        // Latitude > 90
        $this->assertFalse($this->gpsService->validarCoordenadas(91, 0));

        // Latitude < -90
        $this->assertFalse($this->gpsService->validarCoordenadas(-91, 0));

        // Longitude > 180
        $this->assertFalse($this->gpsService->validarCoordenadas(0, 181));

        // Longitude < -180
        $this->assertFalse($this->gpsService->validarCoordenadas(0, -181));

        // Non-numeric
        $this->assertFalse($this->gpsService->validarCoordenadas('abc', 0));
        $this->assertFalse($this->gpsService->validarCoordenadas(0, 'xyz'));
    }

    /**
     * Test finding closest point from multiple points
     */
    public function test_encontrar_punto_mas_cercano(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;

        $puntos = [
            ['lat' => -17.3895, 'lng' => -66.1568],  // Cochabamba (~300 km)
            ['lat' => -19.0322, 'lng' => -65.2596],  // Sucre (~450 km)
            ['lat' => -16.8854, 'lng' => -68.1131],  // Oruro (~40 km) - closest
            ['lat' => -14.5085, 'lng' => -67.5148],  // Santa Cruz (~500 km)
        ];

        $masCercano = $this->gpsService->encontrarPuntoMasCercano(
            $latOrigen,
            $lngOrigen,
            $puntos
        );

        $this->assertEquals(-16.8854, $masCercano['punto']['lat']);
        $this->assertEquals(-68.1131, $masCercano['punto']['lng']);
        $this->assertLessThan(50, $masCercano['distancia']);
    }

    /**
     * Test sorting points by distance
     */
    public function test_ordenar_por_distancia(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;

        $puntos = [
            ['id' => 1, 'lat' => -17.3895, 'lng' => -66.1568],   // Cochabamba
            ['id' => 2, 'lat' => -19.0322, 'lng' => -65.2596],   // Sucre
            ['id' => 3, 'lat' => -16.8854, 'lng' => -68.1131],   // Oruro (closest)
            ['id' => 4, 'lat' => -14.5085, 'lng' => -67.5148],   // Santa Cruz
        ];

        $ordenados = $this->gpsService->ordenarPorDistancia(
            $latOrigen,
            $lngOrigen,
            $puntos
        );

        // First should be Oruro (closest)
        $this->assertEquals(3, $ordenados[0]['id']);
        // Last should be Sucre (furthest)
        $this->assertEquals(2, $ordenados[3]['id']);
    }

    /**
     * Test geofencing (within radius)
     */
    public function test_esta_dentro_del_radio(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;
        $latPunto = -16.8854;
        $lngPunto = -68.1131;

        // Oruro is ~40 km from La Paz
        $this->assertTrue($this->gpsService->estaDentroDelRadio(
            $latOrigen,
            $lngOrigen,
            $latPunto,
            $lngPunto,
            50 // 50 km radius
        ));

        $this->assertFalse($this->gpsService->estaDentroDelRadio(
            $latOrigen,
            $lngOrigen,
            $latPunto,
            $lngPunto,
            30 // 30 km radius
        ));
    }

    /**
     * Test calculating midpoint between two coordinates
     */
    public function test_calcular_punto_medio(): void
    {
        // Equator and Prime Meridian
        $midpoint = $this->gpsService->calcularPuntoMedio(0, 0, 2, 2);

        $this->assertAlmostEqual(1, $midpoint['lat'], 2);
        $this->assertAlmostEqual(1, $midpoint['lng'], 2);
    }

    /**
     * Test distance calculation along route
     */
    public function test_calcular_distancia_ruta(): void
    {
        $puntos = [
            ['lat' => -16.5, 'lng' => -68.15],      // Starting point
            ['lat' => -16.8854, 'lng' => -68.1131], // Oruro
            ['lat' => -17.3895, 'lng' => -66.1568], // Cochabamba
        ];

        $distanciaTotal = $this->gpsService->calcularDistanciaRuta($puntos);

        // Should be sum of La Paz->Oruro + Oruro->Cochabamba
        // La Paz->Oruro: ~40 km
        // Oruro->Cochabamba: ~270 km
        // Total: ~310 km
        $this->assertGreaterThan(300, $distanciaTotal);
        $this->assertLessThan(320, $distanciaTotal);
    }

    /**
     * Test time estimation
     */
    public function test_estimar_tiempo_viaje(): void
    {
        // 100 km at 40 km/h = 2.5 hours = 150 minutes
        $tiempo = $this->gpsService->estimarTiempoViaje(100, 40);

        $this->assertEquals(150, $tiempo);
    }

    /**
     * Test time estimation with default speed
     */
    public function test_estimar_tiempo_viaje_velocidad_defecto(): void
    {
        // Default speed is 40 km/h
        $tiempo = $this->gpsService->estimarTiempoViaje(40);

        $this->assertEquals(60, $tiempo); // 1 hour
    }

    /**
     * Helper method for approximate equality
     */
    private function assertAlmostEqual($expected, $actual, $precision = 2): void
    {
        $this->assertEqualsWithDelta($expected, $actual, pow(10, -$precision));
    }
}
