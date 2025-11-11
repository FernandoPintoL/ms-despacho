<?php

namespace Tests\Unit\GraphQL\Queries;

use Tests\TestCase;
use App\Models\Despacho;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EstadisticasDespachosQueryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que debe calcular estadísticas de despachos correctamente
     */
    public function test_debe_calcular_estadisticas_correctamente()
    {
        // Crear despachos con diferentes estados
        Despacho::factory()->count(2)->create(['estado' => 'en_camino']);
        Despacho::factory()->count(3)->create(['estado' => 'en_sitio']);
        Despacho::factory()->count(1)->create(['estado' => 'pendiente']);
        Despacho::factory()->count(2)->create(['estado' => 'completado']);
        Despacho::factory()->count(1)->create(['estado' => 'cancelado']);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    pendientes
                    en_camino
                    en_sitio
                    cancelados
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 9,
            'completados' => 2,
            'pendientes' => 1,
            'en_camino' => 2,
            'en_sitio' => 3,
            'cancelados' => 1,
        ]);
    }

    /**
     * Test que debe calcular estadísticas por prioridad
     */
    public function test_debe_calcular_estadisticas_por_prioridad()
    {
        Despacho::factory()->count(2)->create(['prioridad' => 'critica']);
        Despacho::factory()->count(3)->create(['prioridad' => 'alta']);
        Despacho::factory()->count(2)->create(['prioridad' => 'media']);
        Despacho::factory()->count(1)->create(['prioridad' => 'baja']);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    critica
                    alta
                    media
                    baja
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 8,
            'critica' => 2,
            'alta' => 3,
            'media' => 2,
            'baja' => 1,
        ]);
    }

    /**
     * Test que debe respetar el filtro de horas
     */
    public function test_debe_respetar_filtro_de_horas()
    {
        // Crear despachos recientes (última hora)
        Despacho::factory()->count(3)->create([
            'created_at' => now()->subMinutes(30),
        ]);

        // Crear despachos antiguos (hace 30 horas)
        Despacho::factory()->count(2)->create([
            'created_at' => now()->subHours(30),
        ]);

        // Query últimas 24 horas
        $query24 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                }
            }
        GQL;

        $response24 = $this->graphQL($query24);
        $response24->assertJsonPath('data.estadisticasDespachos.total', 3);

        // Query últimas 2 horas
        $query2 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 2) {
                    total
                }
            }
        GQL;

        $response2 = $this->graphQL($query2);
        $response2->assertJsonPath('data.estadisticasDespachos.total', 3);

        // Query últimas 12 horas
        $query12 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 12) {
                    total
                }
            }
        GQL;

        $response12 = $this->graphQL($query12);
        $response12->assertJsonPath('data.estadisticasDespachos.total', 3);
    }

    /**
     * Test que debe calcular tasa de completación
     */
    public function test_debe_calcular_tasa_completacion()
    {
        // Crear 10 despachos: 7 completados, 3 pendientes
        Despacho::factory()->count(7)->create(['estado' => 'completado']);
        Despacho::factory()->count(3)->create(['estado' => 'pendiente']);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    tasa_completcion
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', function ($stats) {
            $this->assertEquals(10, $stats['total']);
            $this->assertEquals(7, $stats['completados']);
            // 7 completados de 10 totales = 70%
            $this->assertEquals(70, $stats['tasa_completcion']);

            return true;
        });
    }

    /**
     * Test que debe retornar 0 cuando no hay despachos
     */
    public function test_debe_retornar_ceros_sin_despachos()
    {
        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    pendientes
                    en_camino
                    en_sitio
                    cancelados
                    critica
                    alta
                    media
                    baja
                    tasa_completcion
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 0,
            'completados' => 0,
            'pendientes' => 0,
            'en_camino' => 0,
            'en_sitio' => 0,
            'cancelados' => 0,
            'critica' => 0,
            'alta' => 0,
            'media' => 0,
            'baja' => 0,
            'tasa_completcion' => 0,
        ]);
    }

    /**
     * Test que debe incluir todos los campos esperados
     */
    public function test_debe_incluir_todos_los_campos_esperados()
    {
        Despacho::factory()->count(5)->create();

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    pendientes
                    en_camino
                    en_sitio
                    cancelados
                    critica
                    alta
                    media
                    baja
                    tasa_completcion
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', function ($stats) {
            $expectedKeys = [
                'total',
                'completados',
                'pendientes',
                'en_camino',
                'en_sitio',
                'cancelados',
                'critica',
                'alta',
                'media',
                'baja',
                'tasa_completcion',
            ];

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $stats);
            }

            return true;
        });
    }

    /**
     * Test que debe manejar combinaciones complejas de estados y prioridades
     */
    public function test_debe_manejar_combinaciones_complejas()
    {
        // Crear despachos con combinaciones
        Despacho::factory()->count(5)->create([
            'estado' => 'en_camino',
            'prioridad' => 'alta',
        ]);

        Despacho::factory()->count(3)->create([
            'estado' => 'en_sitio',
            'prioridad' => 'critica',
        ]);

        Despacho::factory()->count(2)->create([
            'estado' => 'completado',
            'prioridad' => 'media',
        ]);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    en_camino
                    en_sitio
                    completados
                    alta
                    critica
                    media
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 10,
            'en_camino' => 5,
            'en_sitio' => 3,
            'completados' => 2,
            'alta' => 5,
            'critica' => 3,
            'media' => 2,
        ]);
    }

    /**
     * Test que debe calcular correctamente con múltiples rangos de horas
     */
    public function test_debe_calcular_correctamente_multiples_rangos_horas()
    {
        $now = Carbon::now();

        // Último 1 hora
        Despacho::factory()->count(2)->create([
            'created_at' => $now->copy()->subMinutes(30),
        ]);

        // 1-6 horas
        Despacho::factory()->count(3)->create([
            'created_at' => $now->copy()->subHours(3),
        ]);

        // 6-12 horas
        Despacho::factory()->count(2)->create([
            'created_at' => $now->copy()->subHours(9),
        ]);

        // 12-24 horas
        Despacho::factory()->count(1)->create([
            'created_at' => $now->copy()->subHours(18),
        ]);

        // Query últimas 24 horas
        $query24 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                }
            }
        GQL;

        $response24 = $this->graphQL($query24);
        $response24->assertJsonPath('data.estadisticasDespachos.total', 8);

        // Query últimas 12 horas
        $query12 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 12) {
                    total
                }
            }
        GQL;

        $response12 = $this->graphQL($query12);
        $response12->assertJsonPath('data.estadisticasDespachos.total', 7);

        // Query últimas 6 horas
        $query6 = <<<'GQL'
            query {
                estadisticasDespachos(horas: 6) {
                    total
                }
            }
        GQL;

        $response6 = $this->graphQL($query6);
        $response6->assertJsonPath('data.estadisticasDespachos.total', 5);
    }

    /**
     * Test que debe tener tasa de completación de 100% con todos completados
     */
    public function test_debe_tener_tasa_100_porciento_completados()
    {
        Despacho::factory()->count(5)->create(['estado' => 'completado']);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    tasa_completcion
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 5,
            'completados' => 5,
            'tasa_completcion' => 100,
        ]);
    }

    /**
     * Test que debe tener tasa de completación de 0% sin completados
     */
    public function test_debe_tener_tasa_0_porciento_sin_completados()
    {
        Despacho::factory()->count(5)->create(['estado' => 'pendiente']);

        $query = <<<'GQL'
            query {
                estadisticasDespachos(horas: 24) {
                    total
                    completados
                    tasa_completcion
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.estadisticasDespachos', [
            'total' => 5,
            'completados' => 0,
            'tasa_completcion' => 0,
        ]);
    }
}
