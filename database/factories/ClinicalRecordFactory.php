<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;

class ClinicalRecordFactory extends Factory
{
    public function definition(): array
    {
        $tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        
        return [
            'patient_id' => Patient::factory(),
            'tipo_sangre' => fake()->randomElement($tiposSangre),
            'alergias' => fake()->randomElement(['Ninguna', 'Penicilina', 'Látex', 'Ibuprofeno']),
            'enfermedades_cronicas' => fake()->randomElement(['Ninguna', 'Diabetes', 'Hipertensión', 'Asma']),
            'medicamentos_actuales' => fake()->boolean(20) ? 'Loratadina 10mg' : 'Ninguno',
            'notas_medicas' => fake()->optional()->sentence(),
        ];
    }
}
