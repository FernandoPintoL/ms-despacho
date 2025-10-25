<?php

namespace Database\Seeders;

use App\Models\Personal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personal = [
            // Paramédicos
            [
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'ci' => '12345678',
                'rol' => 'paramedico',
                'especialidad' => 'Emergencias',
                'experiencia' => 5,
                'estado' => 'disponible',
                'telefono' => '70123456',
                'email' => 'juan.perez@ambulancias.bo',
            ],
            [
                'nombre' => 'María',
                'apellido' => 'González',
                'ci' => '23456789',
                'rol' => 'paramedico',
                'especialidad' => 'Trauma',
                'experiencia' => 8,
                'estado' => 'disponible',
                'telefono' => '71234567',
                'email' => 'maria.gonzalez@ambulancias.bo',
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Rodríguez',
                'ci' => '34567890',
                'rol' => 'paramedico',
                'especialidad' => 'Pediatría',
                'experiencia' => 3,
                'estado' => 'disponible',
                'telefono' => '72345678',
                'email' => 'carlos.rodriguez@ambulancias.bo',
            ],
            
            // Conductores
            [
                'nombre' => 'Pedro',
                'apellido' => 'Mamani',
                'ci' => '45678901',
                'rol' => 'conductor',
                'especialidad' => null,
                'experiencia' => 10,
                'estado' => 'disponible',
                'telefono' => '73456789',
                'email' => 'pedro.mamani@ambulancias.bo',
            ],
            [
                'nombre' => 'Luis',
                'apellido' => 'Quispe',
                'ci' => '56789012',
                'rol' => 'conductor',
                'especialidad' => null,
                'experiencia' => 7,
                'estado' => 'disponible',
                'telefono' => '74567890',
                'email' => 'luis.quispe@ambulancias.bo',
            ],
            
            // Médicos
            [
                'nombre' => 'Ana',
                'apellido' => 'Fernández',
                'ci' => '67890123',
                'rol' => 'medico',
                'especialidad' => 'Medicina de Emergencia',
                'experiencia' => 12,
                'estado' => 'disponible',
                'telefono' => '75678901',
                'email' => 'ana.fernandez@ambulancias.bo',
            ],
            [
                'nombre' => 'Roberto',
                'apellido' => 'Sánchez',
                'ci' => '78901234',
                'rol' => 'medico',
                'especialidad' => 'Cardiología',
                'experiencia' => 15,
                'estado' => 'descanso',
                'telefono' => '76789012',
                'email' => 'roberto.sanchez@ambulancias.bo',
            ],
            
            // Enfermeros
            [
                'nombre' => 'Laura',
                'apellido' => 'Vargas',
                'ci' => '89012345',
                'rol' => 'enfermero',
                'especialidad' => 'Cuidados Intensivos',
                'experiencia' => 6,
                'estado' => 'disponible',
                'telefono' => '77890123',
                'email' => 'laura.vargas@ambulancias.bo',
            ],
            [
                'nombre' => 'Sofia',
                'apellido' => 'Morales',
                'ci' => '90123456',
                'rol' => 'enfermero',
                'especialidad' => 'Emergencias',
                'experiencia' => 4,
                'estado' => 'disponible',
                'telefono' => '78901234',
                'email' => 'sofia.morales@ambulancias.bo',
            ],
        ];

        foreach ($personal as $persona) {
            DB::table('personal')->insert([
                'nombre' => $persona['nombre'],
                'apellido' => $persona['apellido'],
                'ci' => $persona['ci'],
                'rol' => $persona['rol'],
                'especialidad' => $persona['especialidad'],
                'experiencia' => $persona['experiencia'],
                'estado' => $persona['estado'],
                'telefono' => $persona['telefono'],
                'email' => $persona['email'],
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ]);
        }
    }
}
