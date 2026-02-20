<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Tire;

class InventoryEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $sku;
    protected $warehouse;
    protected $supplier;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user with permissions
        $this->user = User::factory()->create();
        // Assume user has all permissions for simplified testing or mock middleware
        $this->actingAs($this->user);

        // Setup infrastructure
        $this->warehouse = Warehouse::create(['name' => 'Test Warehouse', 'location' => 'Test Loc', 'status' => 'active']);
        $this->supplier = Supplier::create(['supplier_name' => 'Test Supplier', 'supplier_code' => 'SUP01']);
        
        // Create SKU
        $this->sku = Sku::create([
            'sku_code' => 'TEST-SKU-001',
            'brand' => 'TestBrand',
            'model' => 'TestModel',
            'size' => '295/80R22.5',
            'sku_name' => 'Test Tire',
            'status' => 'active',
            'default_warehouse_id' => $this->warehouse->id
        ]);
        
        // Create Vehicle
        $this->vehicle = Vehicle::create([
            'registration_number' => 'TEST-VEH-01',
            'model' => 'Test Truck',
            'type' => 'Truck',
            'status' => 'active',
            'odometer' => 10000
        ]);
    }

    /** @test */
    public function it_can_receive_tires_creating_unified_assets()
    {
        $payload = [
            'sku_code' => $this->sku->sku_code,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'entry_mode' => 'INDIVIDUAL',
            'tires' => [
                [
                    'dot_code' => 'DOT1234',
                    'manufacture_week' => 10,
                    'manufacture_year' => 2024,
                    'condition' => 'NEW',
                    'purchase_price' => 500
                ]
            ]
        ];

        $response = $this->postJson('/api/inventory/receive', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
            
        // Verify Tire created in table
        $this->assertDatabaseHas('tires', [
            'dot_code' => 'DOT1234',
            'sku_id' => $this->sku->id,
            'status' => 'available'
        ]);
        
        // Verify SKU stock updated
        $this->assertEquals(1, $this->sku->fresh()->current_stock);
        $this->assertEquals(1, $this->sku->fresh()->calculated_stock);
    }

    /** @test */
    public function it_can_configure_vehicle_axles()
    {
        $positions = [
            ['position_code' => 'FL', 'axle_number' => 1, 'side' => 'L', 'tire_type_requirement' => 'STEER'],
            ['position_code' => 'FR', 'axle_number' => 1, 'side' => 'R', 'tire_type_requirement' => 'STEER']
        ];

        $response = $this->postJson("/api/operations/vehicles/{$this->vehicle->id}/axle-configuration", [
            'positions' => $positions
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('axle_positions', [
            'vehicle_id' => $this->vehicle->id,
            'position_code' => 'FL'
        ]);
    }

    /** @test */
    public function it_can_issue_tire_to_axle_position()
    {
        // 1. Receive Tire
        $tire = Tire::create([
            'unique_tire_id' => 'TIRE-ISSUE-TEST',
            'sku_id' => $this->sku->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'available',
            'dot_code' => 'DOTISSUE'
        ]);

        // 2. Configure Axle
        $this->postJson("/api/operations/vehicles/{$this->vehicle->id}/axle-configuration", [
            'positions' => [
                ['position_code' => 'FL', 'axle_number' => 1, 'side' => 'L']
            ]
        ]);

        // 3. Issue Tire
        $response = $this->postJson('/api/operations/issue', [
            'tire_id' => $tire->id,
            'vehicle_id' => $this->vehicle->id,
            'position' => 'FL',
            'odometer' => 11000
        ]);

        $response->assertStatus(200);

        // 4. Verify
        $this->assertDatabaseHas('tires', [
            'id' => $tire->id,
            'status' => 'mounted',
            'vehicle_id' => $this->vehicle->id,
            'position' => 'FL'
        ]);
        
        $this->assertDatabaseHas('axle_positions', [
            'vehicle_id' => $this->vehicle->id,
            'position_code' => 'FL',
            'tire_id' => $tire->id
        ]);
        
        // Verify SKU stock decreased
        $this->assertEquals(0, $this->sku->fresh()->calculated_stock);
    }
}
