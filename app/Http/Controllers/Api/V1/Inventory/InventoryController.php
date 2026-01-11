<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sku;
use App\Models\InventoryTire;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/inventory/receive",
     *     summary="Receive tires into inventory",
     *     description="Receive individual tires with DOT codes or bulk quantities",
     *     tags={"Inventory"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku_code", "warehouse_id", "supplier_id", "entry_mode", "tires"},
     *             @OA\Property(property="sku_code", type="string", example="TIRE-MIC-205-55-16"),
     *             @OA\Property(property="warehouse_id", type="integer", example=1),
     *             @OA\Property(property="supplier_id", type="integer", example=1),
     *             @OA\Property(property="entry_mode", type="string", enum={"INDIVIDUAL", "BULK"}, example="INDIVIDUAL"),
     *             @OA\Property(property="received_date", type="string", format="date", example="2026-01-11"),
     *             @OA\Property(
     *                 property="tires",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"dot_code", "manufacture_week", "manufacture_year", "condition"},
     *                     @OA\Property(property="dot_code", type="string", example="DOT8K3H234"),
     *                     @OA\Property(property="manufacture_week", type="integer", minimum=1, maximum=52, example=12),
     *                     @OA\Property(property="manufacture_year", type="integer", example=2024),
     *                     @OA\Property(property="condition", type="string", enum={"NEW", "USED", "REFURBISHED", "DAMAGED"}, example="NEW"),
     *                     @OA\Property(property="purchase_price", type="number", example=100.00),
     *                     @OA\Property(property="notes", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Tires received successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", description="SKU, warehouse, or supplier not found"),
     *     @OA\Response(response="422", description="Validation error")
     * )
     */
    public function receive(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'sku_code' => 'required|string|exists:skus,sku_code',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'supplier_id' => 'required|integer|exists:suppliers,id',
                'entry_mode' => ['required', Rule::in(['INDIVIDUAL', 'BULK'])],
                'received_date' => 'nullable|date',
                'tires' => 'required|array|min:1',
                'tires.*.dot_code' => 'required|string|unique:inventory_tires,dot_code',
                'tires.*.manufacture_week' => 'required|integer|min:1|max:52',
                'tires.*.manufacture_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
                'tires.*.condition' => ['required', Rule::in(['NEW', 'USED', 'REFURBISHED', 'DAMAGED'])],
                'tires.*.purchase_price' => 'nullable|numeric|min:0',
                'tires.*.notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Get the SKU
            $sku = Sku::where('sku_code', $validated['sku_code'])->firstOrFail();

            // Verify SKU is active
            if (!$sku->isActive()) {
                return response()->json([
                    'error' => 'Inactive SKU',
                    'message' => 'Cannot receive tires for an inactive SKU'
                ], 422);
            }

            // Get warehouse and supplier
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $supplier = Supplier::findOrFail($validated['supplier_id']);

            $receivedDate = $validated['received_date'] ?? now()->toDateString();
            $receivedTires = [];
            $tireCount = count($validated['tires']);

            // Create individual tire records
            foreach ($validated['tires'] as $tireData) {
                $tire = InventoryTire::create([
                    'sku_id' => $sku->id,
                    'warehouse_id' => $warehouse->id,
                    'supplier_id' => $supplier->id,
                    'dot_code' => strtoupper($tireData['dot_code']),
                    'manufacture_week' => $tireData['manufacture_week'],
                    'manufacture_year' => $tireData['manufacture_year'],
                    'condition' => $tireData['condition'],
                    'status' => 'AVAILABLE',
                    'received_date' => $receivedDate,
                    'purchase_price' => $tireData['purchase_price'] ?? null,
                    'notes' => $tireData['notes'] ?? null,
                ]);

                $receivedTires[] = $tire;
            }

            // Update SKU stock count
            $sku->increment('current_stock', $tireCount);

            // Create stock movement record
            StockMovement::create([
                'tire_id' => null, // This is for individual tire movements
                'to_warehouse_id' => $warehouse->id,
                'type' => 'purchase',
                'quantity' => $tireCount,
                'notes' => "Received {$tireCount} tires from {$supplier->supplier_name}",
                'user_id' => auth()->id() ?? null,
            ]);

            DB::commit();

            Log::info('Inventory received', [
                'sku_code' => $sku->sku_code,
                'tire_count' => $tireCount,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$tireCount} tire(s) received successfully",
                'data' => [
                    'received_count' => $tireCount,
                    'sku_code' => $sku->sku_code,
                    'sku_name' => $sku->sku_name,
                    'new_stock_level' => $sku->fresh()->current_stock,
                    'warehouse' => $warehouse->name,
                    'supplier' => $supplier->supplier_name,
                    'tires' => $receivedTires->map(function ($tire) {
                        return [
                            'id' => $tire->id,
                            'dot_code' => $tire->dot_code,
                            'qr_code' => $tire->qr_code,
                            'condition' => $tire->condition,
                            'status' => $tire->status,
                        ];
                    }),
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Please check your input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Resource not found',
                'message' => 'SKU, warehouse, or supplier not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Inventory receive error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to receive inventory',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/tires",
     *     summary="List all inventory tires with filtering",
     *     tags={"Inventory"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sku_code", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="condition", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="dot_code", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response="200", description="Paginated list of inventory tires")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = InventoryTire::with(['sku', 'warehouse', 'supplier']);

            // Filter by SKU
            if ($request->has('sku_code')) {
                $sku = Sku::where('sku_code', $request->sku_code)->first();
                if ($sku) {
                    $query->where('sku_id', $sku->id);
                }
            }

            // Filter by warehouse
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by condition
            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            // Search by DOT code
            if ($request->has('dot_code')) {
                $query->where('dot_code', 'ilike', '%' . $request->dot_code . '%');
            }

            $perPage = $request->input('per_page', 20);
            $tires = $query->latest()->paginate($perPage);

            return response()->json($tires, 200);

        } catch (\Exception $e) {
            Log::error('Inventory tires list error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to retrieve inventory tires',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/tires/{dot_code}",
     *     summary="Get tire by DOT code",
     *     tags={"Inventory"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="dot_code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Tire details"),
     *     @OA\Response(response="404", description="Tire not found")
     * )
     */
    public function show($dotCode)
    {
        try {
            $tire = InventoryTire::with(['sku', 'warehouse', 'supplier'])
                ->where('dot_code', strtoupper($dotCode))
                ->firstOrFail();

            $tireData = $tire->toArray();
            $tireData['age_in_months'] = $tire->getAgeInMonths();
            $tireData['age_in_weeks'] = $tire->getAgeInWeeks();
            $tireData['is_expired'] = $tire->isExpired();
            $tireData['is_available'] = $tire->isAvailable();

            return response()->json([
                'success' => true,
                'data' => $tireData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Tire not found',
                'message' => "Tire with DOT code '{$dotCode}' does not exist"
            ], 404);

        } catch (\Exception $e) {
            Log::error('Inventory tire show error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to retrieve tire',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/inventory/tires/{dot_code}/status",
     *     summary="Update tire status",
     *     tags={"Inventory"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="dot_code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"AVAILABLE", "RESERVED", "SOLD", "SCRAPPED", "IN_USE"})
     *         )
     *     ),
     *     @OA\Response(response="200", description="Status updated successfully")
     * )
     */
    public function updateStatus(Request $request, $dotCode)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['AVAILABLE', 'RESERVED', 'SOLD', 'SCRAPPED', 'IN_USE'])],
            ]);

            $tire = InventoryTire::where('dot_code', strtoupper($dotCode))->firstOrFail();
            
            $oldStatus = $tire->status;
            $tire->status = $validated['status'];

            // Set sold date if status is SOLD
            if ($validated['status'] === 'SOLD' && $oldStatus !== 'SOLD') {
                $tire->sold_date = now()->toDateString();
                
                // Decrement SKU stock
                $tire->sku->decrement('current_stock');
            }

            $tire->save();

            Log::info('Tire status updated', [
                'dot_code' => $tire->dot_code,
                'old_status' => $oldStatus,
                'new_status' => $tire->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tire status updated successfully',
                'data' => $tire
            ], 200);

        } catch (\Exception $e) {
            Log::error('Tire status update error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to update tire status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
