<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Schedule;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'schedule_id' => Schedule::factory(),
            'fecha' => fake()->dateTimeBetween('-4 weeks', '+2 weeks')->format('Y-m-d'),
            'hora_inicio' => '08:00:00',
            'motivo_consulta' => fake()->sentence(),
            'estado' => fake()->randomElement(['pendiente', 'confirmada', 'completada', 'cancelada']),
            'notas' => fake()->optional()->paragraph(),
        ];
    }
}
