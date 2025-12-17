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
        $brands = ['Michelin', 'Bridgestone', 'Goodyear', 'Continental', 'Pirelli'];
        $models = ['X Multi', 'Ecopia', 'Endurance', 'CrossContact', 'P Zero'];
        $sizes = ['295/80R22.5', '315/80R22.5', '11R22.5', '12R22.5'];
        
        $mainWarehouse = \App\Models\Warehouse::first(); // Assuming seeded

        if (!$mainWarehouse) return;

        for ($i = 0; $i < 50; $i++) {
            $brand = $brands[array_rand($brands)];
            
            \App\Models\Tire::create([
                'unique_tire_id' => 'TIRE-' . strtoupper(uniqid()),
                'serial_number' => 'SN' . rand(100000, 999999),
                'brand' => $brand,
                'model' => $models[array_rand($models)],
                'size' => $sizes[array_rand($sizes)],
                'cost' => rand(300, 800),
                'vendor' => 'Global Tires Inc.',
                'purchase_date' => now()->subDays(rand(1, 365)),
                'warehouse_id' => $mainWarehouse->id,
                'status' => 'available',
            ]);
        }
    }
}
