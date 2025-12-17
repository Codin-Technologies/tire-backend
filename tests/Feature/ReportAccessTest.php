<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_admin_can_access_reports()
    {
        $this->seedDetails();
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Administrator');
        // Refresh permissions specifically
        $admin->refresh();

        $response = $this->actingAs($admin)->getJson('/api/reports/low-stock');
        $response->assertStatus(200);
    }

    public function test_manager_can_access_reports()
    {
        $this->seedDetails();
        $manager = \App\Models\User::factory()->create();
        $manager->assignRole('Fleet Manager');
        $manager->refresh();

        $response = $this->actingAs($manager)->getJson('/api/reports/low-stock');
        $response->assertStatus(200);
    }

    public function test_technician_cannot_access_reports()
    {
        $this->seedDetails();
        $tech = \App\Models\User::factory()->create();
        $tech->assignRole('Technician');
        $tech->refresh();

        $response = $this->actingAs($tech)->getJson('/api/reports/low-stock');
        $response->assertStatus(403);
    }

    private function seedDetails() {
         app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
         $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
         app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
