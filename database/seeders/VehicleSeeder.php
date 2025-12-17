<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Truck', 'Bus', 'Van', 'Trailer'];
        $models = ['Actros', 'FH16', 'Scania R', 'Sprinter', 'HiAce'];
        
        for ($i = 0; $i < 10; $i++) {
            \App\Models\Vehicle::create([
                'registration_number' => 'T' . rand(100, 999) . ' ' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3)),
                'fleet_number' => 'F-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'model' => $models[array_rand($models)],
                'type' => $types[array_rand($types)],
                'axle_config' => [2, 4, 6, 8][array_rand([0,1,2,3])],
                'odometer' => rand(10000, 500000),
                'year' => rand(2015, 2024),
                'status' => 'active',
            ]);
        }
    }
}
