<?php

namespace Database\Seeders;

use App\Models\Ambulancia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmbulanciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ambulancias = [
            [
                'placa' => 'AMB-001',
                'modelo' => 'Mercedes-Benz Sprinter 2023',
                'tipo_ambulancia' => 'avanzada',
                'estado' => 'disponible',
                'caracteristicas' => [
                    'equipamiento' => ['desfibrilador', 'ventilador', 'monitor_signos_vitales'],
                    'capacidad_pacientes' => 2,
                    'año' => 2023,
                ],
                'ubicacion_actual_lat' => -16.5000,
                'ubicacion_actual_lng' => -68.1500,
                'ultima_actualizacion' => DB::raw('GETDATE()'),
            ],
            [
                'placa' => 'AMB-002',
                'modelo' => 'Ford Transit 2022',
                'tipo_ambulancia' => 'intermedia',
                'estado' => 'disponible',
                'caracteristicas' => [
                    'equipamiento' => ['desfibrilador', 'oxigeno', 'camilla'],
                    'capacidad_pacientes' => 1,
                    'año' => 2022,
                ],
                'ubicacion_actual_lat' => -16.5100,
                'ubicacion_actual_lng' => -68.1400,
                'ultima_actualizacion' => DB::raw('GETDATE()'),
            ],
            [
                'placa' => 'AMB-003',
                'modelo' => 'Toyota Hiace 2021',
                'tipo_ambulancia' => 'basica',
                'estado' => 'disponible',
                'caracteristicas' => [
                    'equipamiento' => ['botiquin', 'oxigeno', 'camilla'],
                    'capacidad_pacientes' => 1,
                    'año' => 2021,
                ],
                'ubicacion_actual_lat' => -16.4900,
                'ubicacion_actual_lng' => -68.1600,
                'ultima_actualizacion' => DB::raw('GETDATE()'),
            ],
            [
                'placa' => 'AMB-004',
                'modelo' => 'Mercedes-Benz Sprinter UCI 2024',
                'tipo_ambulancia' => 'uci',
                'estado' => 'disponible',
                'caracteristicas' => [
                    'equipamiento' => ['uci_movil', 'ventilador_avanzado', 'bomba_infusion', 'monitor_completo'],
                    'capacidad_pacientes' => 1,
                    'año' => 2024,
                ],
                'ubicacion_actual_lat' => -16.5050,
                'ubicacion_actual_lng' => -68.1450,
                'ultima_actualizacion' => DB::raw('GETDATE()'),
            ],
            [
                'placa' => 'AMB-005',
                'modelo' => 'Renault Master 2022',
                'tipo_ambulancia' => 'intermedia',
                'estado' => 'mantenimiento',
                'caracteristicas' => [
                    'equipamiento' => ['desfibrilador', 'oxigeno', 'camilla'],
                    'capacidad_pacientes' => 1,
                    'año' => 2022,
                ],
                'ubicacion_actual_lat' => -16.5000,
                'ubicacion_actual_lng' => -68.1500,
                'ultima_actualizacion' => DB::raw('GETDATE()'),
            ],
        ];

        foreach ($ambulancias as $ambulancia) {
            DB::table('ambulancias')->insert([
                'placa' => $ambulancia['placa'],
                'modelo' => $ambulancia['modelo'],
                'tipo_ambulancia' => $ambulancia['tipo_ambulancia'],
                'estado' => $ambulancia['estado'],
                'caracteristicas' => json_encode($ambulancia['caracteristicas']),
                'ubicacion_actual_lat' => $ambulancia['ubicacion_actual_lat'],
                'ubicacion_actual_lng' => $ambulancia['ubicacion_actual_lng'],
                'ultima_actualizacion' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ]);
        }
    }
}
