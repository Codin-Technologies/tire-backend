<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a "High Risk" Fleet of vehicles (Uninspected)
        // This ensures Inspection Compliance report is < 100%
        \App\Models\Vehicle::factory()->count(5)->create([
            'status' => 'active',
            'created_at' => now()->subDays(60)
        ]);

        // 2. Create Low Stock Scenario
        // Only 2 tires of this specific high-value model
        \App\Models\Tire::factory()->count(2)->create([
            'brand' => 'CriticalBrand',
            'model' => 'LowStockModel-X',
            'size' => '295/80R22.5',
            'status' => 'available', // Fixed from 'in_stock' to 'available'
            'warehouse_id' => \App\Models\Warehouse::first()->id ?? null
        ]);

        // 3. Create Healthy Stock Scenario
        // 15 tires of this model
        \App\Models\Tire::factory()->count(15)->create([
            'brand' => 'AbundantBrand',
            'model' => 'HighStockModel-Y',
            'size' => '295/80R22.5',
            'status' => 'available', // Fixed from 'in_stock' to 'available'
             'warehouse_id' => \App\Models\Warehouse::first()->id ?? null
        ]);

        // 4. Create Old Alerts (Resolved) vs New Alerts (Open)
        // This adds depth to Alerts Summary
        $vehicle = \App\Models\Vehicle::first();
        if ($vehicle) {
            \App\Models\Alert::create([
                'vehicle_id' => $vehicle->id,
                'code' => 'OLD_ISSUE',
                'level' => 'warning',
                'message' => 'Old resolved issue',
                'status' => 'resolved',
                'resolved_at' => now()->subDays(10),
                'resolved_by' => 1
            ]);
        }
    }
}
