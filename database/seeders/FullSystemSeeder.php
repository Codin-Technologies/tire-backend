<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\InventoryTire;
use App\Models\Vehicle;
use App\Models\AxlePosition;
use App\Models\Tire;

class FullSystemSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Infrastructure
        $warehouse = Warehouse::create([
            'name' => 'Main Depot',
            'location' => 'Dar es Salaam',
            'status' => 'active'
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'Michelin Official',
            'supplier_code' => 'SUP-MIC-001',
            'contact_person' => 'John Doe',
            'email' => 'sales@michelin.com',
            'status' => 'active'
        ]);

        // 2. Create SKU with Rules
        $sku = Sku::create([
            'sku_code' => 'TIRE-315-80-R22.5-STEER',
            'brand' => 'Michelin',
            'model' => 'X Multi Z',
            'size' => '315/80 R22.5',
            'tire_type' => 'Radial',
            'unit_price' => 450.00,
            'cost_price' => 380.00,
            'status' => 'active',
            'sku_name' => 'Michelin Steer Tire',
            // New Fields
            'retreadable' => true,
            'max_retread_cycles' => 2,
            'expected_mileage' => 120000,
            'min_tread_depth' => 3.0,
            'tire_category' => 'STEER',
            'default_supplier_id' => $supplier->id,
            'default_warehouse_id' => $warehouse->id,
            'preferred_warehouse_id' => $warehouse->id,
            'lead_time_days' => 14,
            'budget_category' => 'PREMIUM',
            'max_age_months' => 60
        ]);

        // 3. Receive Tires (Inventory)
        // Simulate Receive API logic roughly
        $tires = [];
        for ($i = 0; $i < 4; $i++) {
            $tires[] = Tire::create([
                'unique_tire_id' => 'TIRE-' . strtoupper(uniqid()),
                'sku_id' => $sku->id,
                'warehouse_id' => $warehouse->id,
                // 'supplier_id' => $supplier->id, // Tire table stores vendor string, maybe add supplier_id later?
                'vendor' => $supplier->supplier_name,
                'dot_code' => 'DOT' . rand(1000, 9999) . '23',
                'manufacture_week' => 10,
                'manufacture_year' => 2023,
                'condition' => 'NEW',
                'status' => 'available',
                'cost' => 380.00,
                'serial_number' => 'SN-' . rand(10000, 99999)
            ]);
        }

        // 4. Create Vehicle
        $vehicle = Vehicle::create([
            'registration_number' => 'T123 ABC',
            'fleet_number' => 'V-001',
            'model' => 'Scania R500',
            'type' => 'Truck',
            'status' => 'active',
            'odometer' => 50000
        ]);

        // 5. Define Axle Config
        $positions = [
            ['code' => 'A1-L', 'axle' => 1, 'side' => 'L', 'type' => 'STEER'],
            ['code' => 'A1-R', 'axle' => 1, 'side' => 'R', 'type' => 'STEER'],
            ['code' => 'A2-LI', 'axle' => 2, 'side' => 'L', 'type' => 'DRIVE'],
            ['code' => 'A2-LO', 'axle' => 2, 'side' => 'L', 'type' => 'DRIVE'],
            ['code' => 'A2-RI', 'axle' => 2, 'side' => 'R', 'type' => 'DRIVE'],
            ['code' => 'A2-RO', 'axle' => 2, 'side' => 'R', 'type' => 'DRIVE'],
        ];

        foreach ($positions as $pos) {
            AxlePosition::create([
                'vehicle_id' => $vehicle->id,
                'position_code' => $pos['code'],
                'axle_number' => $pos['axle'],
                'side' => $pos['side'],
                'tire_type_requirement' => $pos['type']
            ]);
        }

        // 6. Convert one InventoryTire to Asset and Issue it (Manual, simulating API flow)
        // In real app, receiving creates Asset immediately or Receive API does.
        // Wait, our Receive API creates INVENTORY TIRES. Do we have tires table linked?
        // Ah, our SKU plan said "SKU becomes single source of truth". 
        // We have `tires` table modified to have `sku_id`. 
        // Are we using `inventory_tires` or `tires` for operations?
        // Operations use `tires` table (Tire model).
        // `inventory_tires` seems to be the "received stock batch". 
        // We need a mechanism to convert `InventoryTire` to `Tire` asset when issuing? 
        // OR `InventoryTire` IS the asset? 
        // Looking at the implementation:
        // `InventoryController` creates `InventoryTire`.
        // `TireController` uses `Tire` model.
        // `AxlePosition` links to `Tire`.
        // The Requirement 2 says: "Receive Stock API ... Create multiple TireAssets".
        // My Implementation Plan used `inventory_tires` table for Receive API.
        // But Operations use `Tire` model (`tires` table).
        // This is a disconnect I need to bridge! 
        // Actually, `InventoryTire` might just be a record of receipt. 
        // BUT the plan said "Link Tires to SKU".
        // We modified `tires` table to add `sku_id`.
        // So `Tire` model NOW has `sku_id`.
        // When we "Receive", we should creating `Tire` records (assets) OR `InventoryTire` is the new Asset table?
        // The implementation created `InventoryTire` model and table.
        // But `TireServiceController` uses `Tire` model.
        // Operations are on `Tire` model.
        // So `InventoryController::receive` should probably create `Tire` records too? 
        // OR we should have migrated `Tire` to be the ONE table.
        // Let's check `InventoryController::receive` implementation.
        
    }
}
