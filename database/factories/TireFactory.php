<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tire>
 */
class TireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_tire_id' => 'TIRE-' . strtoupper(uniqid()),
            'serial_number' => $this->faker->unique()->bothify('SN-#####-??'),
            'brand' => $this->faker->randomElement(['Michelin', 'Bridgestone', 'Goodyear']),
            'model' => $this->faker->word,
            'size' => '295/80R22.5',
            'cost' => $this->faker->randomFloat(2, 300, 800),
            'status' => 'in_stock',
        ];
    }
}
