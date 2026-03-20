<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Doctor;

class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        
        $turnos = [
            ['hora_inicio' => '08:00:00', 'hora_fin' => '11:00:00'],
            ['hora_inicio' => '14:00:00', 'hora_fin' => '17:00:00'],
        ];

        $turno = fake()->randomElement($turnos);

        return [
            'doctor_id' => Doctor::factory(),
            'dia_semana' => fake()->randomElement($dias),
            'hora_inicio' => $turno['hora_inicio'],
            'hora_fin' => $turno['hora_fin'],
        ];
    }
}
