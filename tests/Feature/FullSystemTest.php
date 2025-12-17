<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FullSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_tire_lifecycle_flow()
    {
        // 0. Setup: Seed Roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\InspectionSeeder::class); // For types/checklist
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Admin Login
        $admin = \App\Models\User::factory()->create(['email' => 'admin@test.com']);
        $admin->assignRole('Administrator'); 
        
        // Force refresh permissions for this instance
        $admin->refresh();
        
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password', // Default factory password
        ]);
        $loginResponse->assertStatus(200);
        $adminToken = $loginResponse->json('token');

        // 2. Admin Creates Technician
        $techResponse = $this->withToken($adminToken)->postJson('/api/admin/users', [
            'name' => 'John Tech',
            'email' => 'tech@test.com',
            'role' => 'Technician'
        ]);
        $techResponse->assertStatus(201);
        
        // 3. Technician Logs In
        // (Password is random in real app, but for test we can simulate getting it or just force login if we knew it. 
        //  However, since we can't easily grab the random password from email in test without mocking Mail, 
        //  Let's actually Mock Mail to capture it OR reset it. 
        //  Simpler: We act as the User model directly for subsequent requests or Reset it.)
        $tech = \App\Models\User::where('email', 'tech@test.com')->first();
        
        // 4. Admin Creates Stock (Warehouse + Tire)
        $warehouse = \App\Models\Warehouse::factory()->create();
        $tireResponse = $this->withToken($adminToken)->postJson('/api/stock/tires', [
            'brand' => 'Michelin',
            'model' => 'X Multi',
            'size' => '315/80R22.5',
            'cost' => 500,
            'warehouse_id' => $warehouse->id
        ]);
        $tireResponse->assertStatus(201);
        $tireId = $tireResponse->json('id');

        // 5. Admin Creates Vehicle
        $vehicle = \App\Models\Vehicle::factory()->create(['registration_number' => 'TEST-01']);

        // 6. Technician Mounts Tire
        $mountResponse = $this->actingAs($tech)->postJson('/api/operations/mount', [
            'tire_id' => $tireId,
            'vehicle_id' => $vehicle->id,
            'position' => 'FL',
            'odometer' => 10000
        ]);
        $mountResponse->assertStatus(200);

        // 7. Technician Inspects Tire
        $inspectionResponse = $this->actingAs($tech)->postJson('/api/inspections', [
            'vehicle_id' => $vehicle->id,
            'odometer' => 10500,
            'type' => 'routine',
            'tires' => [
                [
                    'tire_id' => $tireId,
                    'pressure' => 110,
                    'tread_depth' => 12, // Good
                    'condition' => 'good'
                ]
            ]
        ]);
        $inspectionResponse->assertStatus(201);
        $inspectionId = $inspectionResponse->json('id');

        // 8. Admin/Manager Approves Inspection
        $approveResponse = $this->withToken($adminToken)->postJson("/api/inspections/{$inspectionId}/approve", [
            'notes' => 'Looks good'
        ]);
        if ($approveResponse->status() !== 200) {
            dump($approveResponse->json());
        }
        $approveResponse->assertStatus(200);

        // 9. Check Reports (Admin)
        $reportResponse = $this->withToken($adminToken)->getJson('/api/reports/tire-performance');
        $reportResponse->assertStatus(200);
        // Should show our Michelin tire
        $reportResponse->assertJsonFragment(['brand' => 'Michelin']);
    }
}
