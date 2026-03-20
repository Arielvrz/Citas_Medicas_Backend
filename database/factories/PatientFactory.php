<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName() . ' ' . fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('1950-01-01', '2005-12-31')->format('Y-m-d'),
            'genero' => fake()->randomElement(['masculino', 'femenino', 'otro']),
            'telefono' => '7' . fake()->numerify('#######'), // Formato salvadoreño aproximado
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->streetAddress() . ', El Salvador',
            'dui' => fake()->unique()->numerify('########-#'),
        ];
    }
}
