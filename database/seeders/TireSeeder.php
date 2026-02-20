<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainWarehouse = \App\Models\Warehouse::first(); // Assuming seeded
        if (!$mainWarehouse) return;

        // Create some SKUs first
        $skus = \App\Models\Sku::factory()->count(10)->create();

        foreach ($skus as $sku) {
            // Create 5-10 tires for each SKU
            \App\Models\Tire::factory()->count(rand(5, 10))->create([
                'sku_id' => $sku->id,
                'warehouse_id' => $mainWarehouse->id,
                'status' => 'available',
                'cost' => $sku->unit_price * 0.7, // Cost is usually lower than price
            ]);
        }
    }
}
