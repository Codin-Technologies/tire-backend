<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sku>
 */
class SkuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brand = $this->faker->randomElement(['Michelin', 'Bridgestone', 'Goodyear', 'Continental', 'Pirelli']);
        $model = $this->faker->word;
        $size = $this->faker->randomElement(['295/80R22.5', '315/80R22.5', '11R22.5', '12R22.5']);
        
        return [
            'sku_code' => strtoupper(substr($brand, 0, 3)) . '-' . strtoupper(substr($model, 0, 3)) . '-' . rand(100, 999),
            'sku_name' => "$brand $model $size",
            'brand' => $brand,
            'model' => $model,
            'size' => $size,
            'unit_price' => $this->faker->randomFloat(2, 300, 800),
            'cost_price' => $this->faker->randomFloat(2, 200, 600),
            'status' => 'active',
            'current_stock' => 0, // Stock managed by Tire/InventoryTire counts usually
            'min_stock_level' => 10,
            'reorder_point' => 5,
        ];
    }
}
