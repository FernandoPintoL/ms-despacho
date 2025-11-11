<?php

namespace Tests\Unit\GraphQL\Queries;

use Tests\TestCase;
use App\Models\Despacho;
use App\Models\Ambulancia;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DespachosRecientesQueryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que debe retornar despachos recientes de las últimas 24 horas
     */
    public function test_debe_obtener_despachos_recientes_de_ultimas_24_horas()
    {
        // Crear despachos en diferentes momentos
        $despachoReciente = Despacho::factory()->create([
            'created_at' => now()->subHours(2),
            'estado' => 'en_camino',
        ]);

        $despachoAntiguo = Despacho::factory()->create([
            'created_at' => now()->subHours(30),
            'estado' => 'completado',
        ]);

        // Query GraphQL
        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24) {
                    id
                    estado
                    prioridad
                    created_at
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', function ($dispatches) use ($despachoReciente, $despachoAntiguo) {
            // Debe contener despacho reciente
            $recentIds = array_column($dispatches, 'id');
            $this->assertContains($despachoReciente->id, $recentIds);

            // No debe contener despacho antiguo
            $this->assertNotContains($despachoAntiguo->id, $recentIds);

            return true;
        });
    }

    /**
     * Test que debe retornar despachos recientes con horas personalizadas
     */
    public function test_debe_obtener_despachos_recientes_con_horas_personalizadas()
    {
        // Crear despachos
        $despacho12h = Despacho::factory()->create([
            'created_at' => now()->subHours(6),
        ]);

        $despacho24h = Despacho::factory()->create([
            'created_at' => now()->subHours(18),
        ]);

        // Query para últimas 12 horas
        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 12) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', function ($dispatches) use ($despacho12h, $despacho24h) {
            $ids = array_column($dispatches, 'id');
            $this->assertContains($despacho12h->id, $ids);
            $this->assertNotContains($despacho24h->id, $ids);

            return true;
        });
    }

    /**
     * Test que debe respetar el límite de registros
     */
    public function test_debe_respetar_limite_de_registros()
    {
        // Crear 10 despachos
        Despacho::factory()->count(10)->create();

        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24, limit: 5) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', function ($dispatches) {
            $this->assertLessThanOrEqual(5, count($dispatches));

            return true;
        });
    }

    /**
     * Test que debe incluir relaciones necesarias
     */
    public function test_debe_incluir_ambulancia_y_personal_en_respuesta()
    {
        $ambulancia = Ambulancia::factory()->create();

        $despacho = Despacho::factory()->create([
            'ambulancia_id' => $ambulancia->id,
            'created_at' => now()->subHours(1),
        ]);

        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24) {
                    id
                    ambulancia {
                        id
                        codigo
                        estado
                    }
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes.0.ambulancia', function ($ambulancia) {
            $this->assertArrayHasKey('id', $ambulancia);
            $this->assertArrayHasKey('codigo', $ambulancia);

            return true;
        });
    }

    /**
     * Test que debe retornar array vacío si no hay despachos recientes
     */
    public function test_debe_retornar_array_vacio_sin_despachos_recientes()
    {
        // No crear despachos

        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', []);
    }

    /**
     * Test que debe filtrar correctamente por fecha de creación
     */
    public function test_debe_filtrar_correctamente_por_fecha_creacion()
    {
        $now = Carbon::now();

        // Crear despacho hace 1 hora
        $despacho1h = Despacho::factory()->create([
            'created_at' => $now->copy()->subHour(),
        ]);

        // Crear despacho hace 5 horas
        $despacho5h = Despacho::factory()->create([
            'created_at' => $now->copy()->subHours(5),
        ]);

        // Crear despacho hace 10 horas
        $despacho10h = Despacho::factory()->create([
            'created_at' => $now->copy()->subHours(10),
        ]);

        // Query para últimas 6 horas
        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 6) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', function ($dispatches) use ($despacho1h, $despacho5h, $despacho10h) {
            $ids = array_column($dispatches, 'id');

            $this->assertContains($despacho1h->id, $ids);
            $this->assertContains($despacho5h->id, $ids);
            $this->assertNotContains($despacho10h->id, $ids);

            return true;
        });
    }

    /**
     * Test que debe retornar despachos con todos los campos esperados
     */
    public function test_debe_retornar_todos_los_campos_esperados()
    {
        Despacho::factory()->create([
            'created_at' => now()->subHours(1),
        ]);

        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24) {
                    id
                    estado
                    prioridad
                    fecha_solicitud
                    ubicacion_origen_lat
                    ubicacion_origen_lng
                    direccion_origen
                    created_at
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes.0', function ($despacho) {
            $this->assertArrayHasKey('id', $despacho);
            $this->assertArrayHasKey('estado', $despacho);
            $this->assertArrayHasKey('prioridad', $despacho);
            $this->assertArrayHasKey('fecha_solicitud', $despacho);
            $this->assertArrayHasKey('ubicacion_origen_lat', $despacho);
            $this->assertArrayHasKey('ubicacion_origen_lng', $despacho);
            $this->assertArrayHasKey('direccion_origen', $despacho);
            $this->assertArrayHasKey('created_at', $despacho);

            return true;
        });
    }

    /**
     * Test que debe estar ordenado por fecha más reciente primero
     */
    public function test_debe_estar_ordenado_por_fecha_mas_reciente()
    {
        $despacho1 = Despacho::factory()->create([
            'created_at' => now()->subHours(1),
        ]);

        $despacho2 = Despacho::factory()->create([
            'created_at' => now()->subHours(2),
        ]);

        $despacho3 = Despacho::factory()->create([
            'created_at' => now()->subHours(3),
        ]);

        $query = <<<'GQL'
            query {
                despachosRecientes(horas: 24) {
                    id
                    created_at
                }
            }
        GQL;

        $response = $this->graphQL($query);

        $response->assertJsonPath('data.despachosRecientes', function ($dispatches) use ($despacho1, $despacho2, $despacho3) {
            // Primer debe ser más reciente (despacho1)
            $this->assertEquals($despacho1->id, $dispatches[0]['id']);
            $this->assertEquals($despacho2->id, $dispatches[1]['id']);
            $this->assertEquals($despacho3->id, $dispatches[2]['id']);

            return true;
        });
    }
}
