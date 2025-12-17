<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Main Warehouse', 'location' => 'Zone A', 'description' => 'Primary storage for new tires'],
            ['name' => 'Service Center South', 'location' => 'Zone B', 'description' => 'Operational stock for mounting'],
            ['name' => 'Recycling Depot', 'location' => 'Zone C', 'description' => 'Collection point for retired tires'],
        ];

        foreach ($warehouses as $wh) {
            \App\Models\Warehouse::create($wh);
        }
    }
}
