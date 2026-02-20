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
            'sku_id' => \App\Models\Sku::factory(), // Create or link to SKU
            'condition' => 'NEW',
            'dot_code' => $this->faker->bothify('####'),
            'manufacture_week' => $this->faker->numberBetween(1, 52),
            'manufacture_year' => $this->faker->year(),
            'cost' => $this->faker->randomFloat(2, 300, 800),
            'status' => 'available',
        ];
    }
}
