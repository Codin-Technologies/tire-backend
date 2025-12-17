<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create specific users first
        $admin = User::firstOrCreate([
            'email' => 'frankkiruma05@gmail.com'
        ], [
            'name' => 'Frank Kiruma',
            'password' => bcrypt('codin@123'),
        ]);

        // 2. Call Seeders in specific order
        $this->call([
            WarehouseSeeder::class, // Replaces StockSeeder which didn't exist
            TireSeeder::class,      // Replaces StockSeeder
            OperationSeeder::class, // Crucial for vehicle/tire ops
            RolesAndPermissionsSeeder::class,
            InspectionSeeder::class,
            ReportDataSeeder::class,
        ]);
        
        // Ensure admin has role
        if ($admin) {
             $admin->assignRole('Administrator');
        }
    }
}
