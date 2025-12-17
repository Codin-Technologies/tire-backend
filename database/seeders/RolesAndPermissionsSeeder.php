<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            // Stock
            'view stock', 'create stock', 'edit stock', 'delete stock',
            // Operations
            'view operations', 'perform operations', // mount/dismount
            // Inspections
            'view inspections', 'perform inspections', 'approve inspections',
            // Reports
            'view reports',
            // Admin
            'manage users', 'view audit logs'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Define Roles and Assign Permissions
        
        // Technician: Can perform work, but not approve or delete stock
        $technician = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Technician']);
        $technician->syncPermissions(['view stock', 'view operations', 'perform operations', 'view inspections', 'perform inspections']);

        // Supervisor/Manager: Can approve, view reports, manage stock
        $manager = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Fleet Manager']);
        $manager->syncPermissions([
            'view stock', 'create stock', 'edit stock', // Stock Management except delete
            'view operations', 'perform operations',
            'view inspections', 'approve inspections', // Approval Authority
            'view reports'
        ]);

        // Workshop Supervisor: Focused on Ops
        $supervisor = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Workshop Supervisor']);
        $supervisor->syncPermissions([
            'view stock', 'create stock', 'edit stock',
            'view operations', 'perform operations',
            'view inspections', 'approve inspections'
        ]);

        // Read-Only
        $readOnly = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Read-Only']);
        $readOnly->syncPermissions(['view stock', 'view operations', 'view inspections', 'view reports']);

        // Administrator: Everything
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->syncPermissions(\Spatie\Permission\Models\Permission::all());

        // 3. Assign Roles to known users
        $admin = \App\Models\User::where('email', 'frankkiruma05@gmail.com')->first();
        if ($admin) $admin->assignRole('Administrator');
        
        $managerUser = \App\Models\User::where('email', 'manager@example.com')->first();
        if ($managerUser) $managerUser->assignRole('Fleet Manager');
        
        $techUser = \App\Models\User::where('email', 'tech@example.com')->first();
        if ($techUser) $techUser->assignRole('Technician');
    }
}
