<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure a user exists
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
            'name' => 'Technician',
            'email' => 'tech@example.com',
            'password' => bcrypt('password'),
        ]);

        $manager = \App\Models\User::where('email', 'manager@example.com')->first() ?? \App\Models\User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        $vehicles = \App\Models\Vehicle::with('tires')->get();
        $statuses = ['pending', 'scheduled', 'completed', 'reviewed', 'approved', 'rejected'];

        foreach ($vehicles as $index => $vehicle) {
            // Cycle through statuses for variety
            $status = $statuses[$index % count($statuses)];
            
            $assignedTo = ($status !== 'pending') ? $user->id : null;
            $scheduledAt = ($status === 'scheduled' || $status === 'pending') ? now()->addDays(rand(1, 5)) : now()->subDays(rand(1, 20));
            $completedAt = in_array($status, ['completed', 'reviewed', 'approved', 'rejected']) ? $scheduledAt->copy()->addHours(2) : null;
            $reviewedBy = in_array($status, ['reviewed', 'approved', 'rejected']) ? $manager->id : null;
            $reviewedAt = $reviewedBy ? $completedAt->copy()->addHours(24) : null;

            // 1. Create Inspection Task
            $inspection = \App\Models\Inspection::create([
                'vehicle_id' => $vehicle->id,
                'status' => $status,
                'assigned_to' => $assignedTo,
                'scheduled_at' => $scheduledAt,
                'completed_at' => $completedAt,
                'completed_at' => $completedAt,
                // 'user_id' => $status === 'pending' ? null : $user->id, // Removed: Column does not exist, covered by assigned_to
                'odometer' => $completedAt ? $vehicle->odometer + rand(100, 500) : null,
                'type' => 'routine',
                'notes' => "Inspection generated in state: $status",
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => $status === 'rejected' ? 'Photos were blurry' : null,
                'created_at' => now()->subDays(rand(20, 30)),
            ]);

            // 2. Add Items only if inspection is completed or further along
            if ($completedAt) {
                foreach ($vehicle->tires as $tire) {
                    $isLowPressure = rand(1, 10) > 8; 
                    $isLowTread = rand(1, 10) > 9; 
                    
                    $pressure = $isLowPressure ? rand(50, 85) : rand(95, 110);
                    $tread = $isLowTread ? rand(10, 30) / 10 : rand(60, 150) / 10; 
                    
                    \App\Models\InspectionItem::create([
                        'inspection_id' => $inspection->id,
                        'tire_id' => $tire->id,
                        'position' => $tire->position,
                        'pressure_psi' => $pressure,
                        'tread_depth_mm' => $tread,
                        'condition' => ($isLowTread || $isLowPressure) ? 'fair' : 'good',
                    ]);
                    
                    // Alerts
                    if ($isLowPressure) {
                        \App\Models\Alert::create([
                            'tire_id' => $tire->id,
                            'vehicle_id' => $vehicle->id,
                            'inspection_id' => $inspection->id,
                            'code' => 'LOW_PRESSURE',
                            'level' => 'warning',
                            'message' => "Low pressure ({$pressure} PSI)",
                            'created_at' => $completedAt,
                            'status' => 'open'
                        ]);
                    }
                }
            }
        }
    }
}
