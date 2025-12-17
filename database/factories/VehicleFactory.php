<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => $this->faker->unique()->bothify('T### ???'),
            'fleet_number' => $this->faker->unique()->bothify('F-###'),
            'model' => $this->faker->randomElement(['Scania R500', 'Volvo FH16', 'Mercedes Actros']),
            'type' => $this->faker->randomElement(['truck', 'bus', 'trailer']),
            'axle_config' => $this->faker->randomElement([4, 6, 8]),
            'odometer' => $this->faker->numberBetween(10000, 500000),
            'status' => 'active',
        ];
    }
}
