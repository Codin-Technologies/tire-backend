<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sku;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SkuController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sku/list",
     *     summary="List all SKUs with filtering and pagination",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active", "inactive", "discontinued"})),
     *     @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="brand", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="low_stock", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="needs_reorder", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by SKU code or name"),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(
     *         response="200",
     *         description="Paginated list of SKUs",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Sku::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by brand
            if ($request->has('brand')) {
                $query->where('brand', 'ilike', '%' . $request->brand . '%');
            }

            // Filter low stock
            if ($request->boolean('low_stock')) {
                $query->lowStock();
            }

            // Filter needs reorder
            if ($request->boolean('needs_reorder')) {
                $query->needsReorder();
            }

            // Search by SKU code or name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('sku_code', 'ilike', '%' . $search . '%')
                      ->orWhere('sku_name', 'ilike', '%' . $search . '%');
                });
            }

            $perPage = $request->input('per_page', 20);
            $skus = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json($skus, 200);

        } catch (\Exception $e) {
            Log::error('SKU List Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve SKUs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sku/{sku_code}",
     *     summary="Get SKU by code",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sku_code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="SKU details"),
     *     @OA\Response(response="404", description="SKU not found")
     * )
     */
    public function show($skuCode)
    {
        try {
            $sku = Sku::where('sku_code', $skuCode)->firstOrFail();
            
            // Add computed fields
            $skuData = $sku->toArray();
            $skuData['is_low_stock'] = $sku->isLowStock();
            $skuData['needs_reorder'] = $sku->needsReorder();
            $skuData['stock_status'] = $sku->stock_status;
            $skuData['profit_margin'] = $sku->profit_margin;

            return response()->json([
                'success' => true,
                'data' => $skuData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'SKU not found',
                'message' => "SKU with code '{$skuCode}' does not exist"
            ], 404);
        } catch (\Exception $e) {
            Log::error('SKU Show Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve SKU',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sku/create",
     *     summary="Create a new SKU",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku_code", "sku_name", "unit_price"},
     *             @OA\Property(property="sku_code", type="string", example="TIRE-MIC-205-55-16"),
     *             @OA\Property(property="sku_name", type="string", example="Michelin Primacy 4 205/55 R16"),
     *             @OA\Property(property="category", type="string", example="Tires"),
     *             @OA\Property(property="unit_of_measure", type="string", example="piece"),
     *             @OA\Property(property="unit_price", type="number", example=150.00),
     *             @OA\Property(property="cost_price", type="number", example=100.00),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "discontinued"}, example="active"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="brand", type="string", example="Michelin"),
     *             @OA\Property(property="model", type="string", example="Primacy 4"),
     *             @OA\Property(property="size", type="string", example="205/55 R16"),
     *             @OA\Property(property="tire_type", type="string", example="summer"),
     *             @OA\Property(property="load_index", type="integer", example=91),
     *             @OA\Property(property="speed_rating", type="string", example="V"),
     *             @OA\Property(property="current_stock", type="integer", example=50),
     *             @OA\Property(property="min_stock_level", type="integer", example=10),
     *             @OA\Property(property="max_stock_level", type="integer", example=200),
     *             @OA\Property(property="reorder_point", type="integer", example=20),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="SKU created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response="422", description="Validation error"),
     *     @OA\Response(response="409", description="SKU code already exists")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sku_code' => 'required|string|unique:skus,sku_code|max:100',
                'sku_name' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'unit_of_measure' => 'nullable|string|max:50',
                'unit_price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'status' => ['nullable', Rule::in(['active', 'inactive', 'discontinued'])],
                'description' => 'nullable|string',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:50',
                'tire_type' => 'nullable|string|max:50',
                'load_index' => 'nullable|integer|min:0',
                'speed_rating' => 'nullable|string|max:5',
                'current_stock' => 'nullable|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'max_stock_level' => 'nullable|integer|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();
            
            $sku = Sku::create($validated);
            
            DB::commit();

            Log::info('SKU created successfully', ['sku_code' => $sku->sku_code]);

            return response()->json([
                'success' => true,
                'message' => 'SKU created successfully',
                'data' => $sku
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Please check your input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Check for unique constraint violation
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'error' => 'Duplicate SKU code',
                    'message' => 'SKU code already exists in the system'
                ], 409);
            }

            Log::error('SKU Creation Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to create SKU due to database error'
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SKU Creation Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to create SKU',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/sku/{sku_code}",
     *     summary="Update an existing SKU",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sku_code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sku_name", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="unit_price", type="number"),
     *             @OA\Property(property="cost_price", type="number"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "discontinued"}),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="current_stock", type="integer"),
     *             @OA\Property(property="min_stock_level", type="integer"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(response="200", description="SKU updated successfully"),
     *     @OA\Response(response="404", description="SKU not found"),
     *     @OA\Response(response="422", description="Validation error")
     * )
     */
    public function update(Request $request, $skuCode)
    {
        try {
            $sku = Sku::where('sku_code', $skuCode)->firstOrFail();

            $validated = $request->validate([
                'sku_name' => 'sometimes|required|string|max:255',
                'category' => 'nullable|string|max:100',
                'unit_of_measure' => 'nullable|string|max:50',
                'unit_price' => 'sometimes|required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'status' => ['nullable', Rule::in(['active', 'inactive', 'discontinued'])],
                'description' => 'nullable|string',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:50',
                'tire_type' => 'nullable|string|max:50',
                'load_index' => 'nullable|integer|min:0',
                'speed_rating' => 'nullable|string|max:5',
                'current_stock' => 'nullable|integer|min:0',
                'min_stock_level' => 'nullable|integer|min:0',
                'max_stock_level' => 'nullable|integer|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();
            
            $sku->update($validated);
            
            DB::commit();

            Log::info('SKU updated successfully', ['sku_code' => $sku->sku_code]);

            return response()->json([
                'success' => true,
                'message' => 'SKU updated successfully',
                'data' => $sku->fresh()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'SKU not found',
                'message' => "SKU with code '{$skuCode}' does not exist"
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Please check your input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SKU Update Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to update SKU',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/sku/{sku_code}",
     *     summary="Delete (soft delete) an SKU",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sku_code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="SKU deleted successfully"),
     *     @OA\Response(response="404", description="SKU not found")
     * )
     */
    public function destroy($skuCode)
    {
        try {
            $sku = Sku::where('sku_code', $skuCode)->firstOrFail();
            
            $sku->delete(); // Soft delete
            
            Log::info('SKU deleted successfully', ['sku_code' => $skuCode]);

            return response()->json([
                'success' => true,
                'message' => 'SKU deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'SKU not found',
                'message' => "SKU with code '{$skuCode}' does not exist"
            ], 404);

        } catch (\Exception $e) {
            Log::error('SKU Delete Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to delete SKU',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sku/bulk-create",
     *     summary="Bulk create SKUs",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"skus"},
     *             @OA\Property(
     *                 property="skus",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"sku_code", "sku_name", "unit_price"},
     *                     @OA\Property(property="sku_code", type="string"),
     *                     @OA\Property(property="sku_name", type="string"),
     *                     @OA\Property(property="unit_price", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Bulk creation completed")
     * )
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'skus' => 'required|array|min:1',
                'skus.*.sku_code' => 'required|string|max:100',
                'skus.*.sku_name' => 'required|string|max:255',
                'skus.*.unit_price' => 'required|numeric|min:0',
            ]);

            $results = [
                'total' => count($request->skus),
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];

            DB::beginTransaction();

            foreach ($request->skus as $index => $skuData) {
                try {
                    // Check for duplicate
                    if (Sku::where('sku_code', $skuData['sku_code'])->exists()) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'row' => $index + 1,
                            'sku_code' => $skuData['sku_code'],
                            'error' => 'SKU code already exists'
                        ];
                        continue;
                    }

                    Sku::create($skuData);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'sku_code' => $skuData['sku_code'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk creation completed',
                'results' => $results
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SKU Bulk Create Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Bulk creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sku/stock-alerts",
     *     summary="Get SKUs with stock alerts (low stock or needs reorder)",
     *     tags={"SKU"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="List of SKUs with stock alerts")
     * )
     */
    public function stockAlerts()
    {
        try {
            $lowStock = Sku::lowStock()->get()->map(function ($sku) {
                return [
                    'sku_code' => $sku->sku_code,
                    'sku_name' => $sku->sku_name,
                    'current_stock' => $sku->current_stock,
                    'min_stock_level' => $sku->min_stock_level,
                    'alert_type' => 'low_stock'
                ];
            });

            $needsReorder = Sku::needsReorder()->get()->map(function ($sku) {
                return [
                    'sku_code' => $sku->sku_code,
                    'sku_name' => $sku->sku_name,
                    'current_stock' => $sku->current_stock,
                    'reorder_point' => $sku->reorder_point,
                    'alert_type' => 'needs_reorder'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $lowStock,
                    'needs_reorder' => $needsReorder,
                    'total_alerts' => $lowStock->count() + $needsReorder->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('SKU Stock Alerts Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to retrieve stock alerts',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
