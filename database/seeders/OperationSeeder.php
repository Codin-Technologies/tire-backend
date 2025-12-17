<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get available Vehicles and Tires
        $vehicles = \App\Models\Vehicle::all();
        $tires = \App\Models\Tire::where('status', 'available')->get();
        
        if ($vehicles->isEmpty() || $tires->isEmpty()) return;

        // 2. Mount some tires
        $positions = ['FL', 'FR', 'RLO', 'RLI', 'RRO', 'RRI'];
        
        foreach ($vehicles as $vehicle) {
             // Mount 2 random tires per vehicle if available
             for ($i = 0; $i < 2; $i++) {
                 if ($tires->isEmpty()) break;
                 
                 $tire = $tires->shift(); // Take one off the stack
                 $position = $positions[$i];
                 $odometer = $vehicle->odometer;

                 // Create Operation
                 \App\Models\TireOperation::create([
                     'tire_id' => $tire->id,
                     'vehicle_id' => $vehicle->id,
                     'type' => 'mount',
                     'odometer' => $odometer,
                     'position' => $position,
                     'notes' => 'Seeded initial mount',
                     'created_at' => now()->subDays(rand(10, 100))
                 ]);

                 // Update Tire
                 $tire->update([
                     'status' => 'mounted',
                     'vehicle_id' => $vehicle->id,
                     'position' => $position,
                     'warehouse_id' => null
                 ]);
                 
                 // Simulate a rotation operation later
                 if (rand(0, 1)) {
                     // Fast forward time
                     $newOdo = $odometer + rand(1000, 5000);
                     $newPos = $i === 0 ? 'FR' : 'FL'; // Simple swap logic
                     
                     \App\Models\TireOperation::create([
                        'tire_id' => $tire->id,
                        'vehicle_id' => $vehicle->id,
                        'type' => 'rotate',
                        'odometer' => $newOdo,
                        'previous_position' => $position,
                        'position' => $newPos,
                        'notes' => 'Routine rotation',
                     ]);
                     
                     $tire->update(['position' => $newPos]);
                 }
             }
        }
    }
}
