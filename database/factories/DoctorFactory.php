<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $especialidades = ['Medicina General', 'Pediatría', 'Cardiología', 'Ginecología', 'Dermatología'];

        return [
            'user_id' => User::factory()->state(['role' => 'medico']),
            'especialidad' => fake()->randomElement($especialidades),
            'numero_colegiado' => 'MED-' . fake()->unique()->numerify('####'),
            'telefono_consultorio' => '2' . fake()->numerify('#######'), // Formato salvadoreño
            'activo' => true,
        ];
    }
}
