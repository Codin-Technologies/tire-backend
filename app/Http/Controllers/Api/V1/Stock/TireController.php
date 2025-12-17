<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TireController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/stock/tires",
     *     summary="List all tires",
     *     tags={"Stock"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="brand", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="size", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="List of tires")
     * )
     */
    public function index(Request $request)
    {
        $query = \App\Models\Tire::query();

        if ($request->has('brand')) {
            $query->where('brand', 'ilike', '%' . $request->brand . '%');
        }
        if ($request->has('size')) {
            $query->where('size', $request->size);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/stock/tires",
     *     summary="Add a single tire",
     *     tags={"Stock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand", "model", "size", "cost"},
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="size", type="string"),
     *             @OA\Property(property="serial_number", type="string"),
     *             @OA\Property(property="cost", type="number"),
     *             @OA\Property(property="vendor", type="string"),
     *             @OA\Property(property="warehouse_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Tire created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'size' => 'required|string',
            'serial_number' => 'nullable|string|unique:tires,serial_number',
            'cost' => 'required|numeric',
            'vendor' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        // Generate Unique ID
        $validated['unique_tire_id'] = 'TIRE-' . strtoupper(uniqid());

        $tire = \App\Models\Tire::create($validated);

        // Record Initial Stock Movement
        if ($tire->warehouse_id) {
            \App\Models\StockMovement::create([
                'tire_id' => $tire->id,
                'to_warehouse_id' => $tire->warehouse_id,
                'type' => 'purchase',
                'notes' => 'Initial stock entry',
                // 'user_id' => auth()->id() // Add auth later
            ]);
        }

        return response()->json($tire, 201);
    }
    /**
     * @OA\Get(
     *     path="/api/stock/tires/{id}/label",
     *     summary="Get Tire QR Code Label",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200", 
     *         description="QR Code Image",
     *         @OA\MediaType(mediaType="image/png")
     *     )
     * )
     */
    public function label($id)
    {
        $tire = \App\Models\Tire::findOrFail($id);
        
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->color(0, 0, 0)
                    ->generate($tire->unique_tire_id);

        return response($qrCode)->header('Content-Type', 'image/png');
    }
    /**
     * @OA\Get(
     *     path="/api/stock/dashboard",
     *     summary="Get Tire Stock Statistics",
     *     tags={"Stock"},
     *     @OA\Response(response="200", description="Dashboard Statistics")
     * )
     */
    public function dashboard()
    {
        $stats = [
            'total_tires' => \App\Models\Tire::count(),
            'by_status' => \App\Models\Tire::selectRaw('status, count(*)')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_brand' => \App\Models\Tire::selectRaw('brand, count(*)')
                ->groupBy('brand')
                ->limit(5)
                ->pluck('count', 'brand'),
            'low_stock_alerts' => 0 // To be implemented with custom logic
        ];

        return response()->json($stats);
    }

    /**
     * @OA\Get(
     *     path="/api/stock/tires/{id}/history",
     *     summary="Get Tire Movement History",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="History list")
     * )
     */
    public function history($id)
    {
        $tire = \App\Models\Tire::findOrFail($id);
        return response()->json($tire->movements()->with(['fromWarehouse', 'toWarehouse'])->latest()->get());
    }
    /**
     * @OA\Get(
     *     path="/api/stock/tires/{id}",
     *     summary="Get single tire details",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Tire details")
     * )
     */
    public function show($id)
    {
        return response()->json(\App\Models\Tire::with('warehouse')->findOrFail($id));
    }

    /**
     * @OA\Put(
     *     path="/api/stock/tires/{id}",
     *     summary="Update tire details",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="size", type="string"),
     *             @OA\Property(property="cost", type="number"),
     *             @OA\Property(property="vendor", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Tire updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $tire = \App\Models\Tire::findOrFail($id);
        $validated = $request->validate([
            'brand' => 'sometimes|required|string',
            'model' => 'sometimes|required|string',
            'size' => 'sometimes|required|string',
            'cost' => 'sometimes|required|numeric',
            'vendor' => 'nullable|string',
            'purchase_date' => 'nullable|date',
        ]);

        $tire->update($validated);
        return response()->json($tire);
    }

    /**
     * @OA\Delete(
     *     path="/api/stock/tires/{id}",
     *     summary="Delete a tire",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="204", description="Tire deleted")
     * )
     */
    public function destroy($id)
    {
        \App\Models\Tire::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/stock/tires/bulk",
     *     summary="Bulk Upload Tires (CSV)",
     *     tags={"Stock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Bulk created")
     * )
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // Assuming first row is header
        
        // Expected header: brand,model,size,cost,vendor,warehouse_id
        
        $created = 0;
        
        while ($row = fgetcsv($handle)) {
            $data = array_combine($header, $row);
            
            // Basic validation and mapping
            if (!isset($data['brand']) || !isset($data['size'])) continue;
            
            $data['unique_tire_id'] = 'TIRE-' . strtoupper(uniqid());
            $tire = \App\Models\Tire::create($data);
            
            if (isset($data['warehouse_id']) && $data['warehouse_id']) {
                 \App\Models\StockMovement::create([
                    'tire_id' => $tire->id,
                    'to_warehouse_id' => $data['warehouse_id'],
                    'type' => 'purchase',
                    'notes' => 'Bulk upload',
                ]);
            }
            $created++;
        }
        
        fclose($handle);
        
        return response()->json(['message' => "$created tires uploaded successfully"], 201);
    }
    /**
     * @OA\Get(
     *     path="/api/stock/tires/{id}/lifecycle",
     *     summary="Get Tire Lifecycle (Operations)",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Lifecycle events")
     * )
     */
    public function lifecycle($id)
    {
        $tire = \App\Models\Tire::findOrFail($id);
        // Assuming relationship 'operations' exists on Tire model, which we need to add, or query directly
        $operations = \App\Models\TireOperation::where('tire_id', $tire->id)
            ->with(['vehicle', 'user', 'photos'])
            ->latest()
            ->get();
            
        return response()->json($operations);
    }
    /**
     * @OA\Get(
     *     path="/api/stock/tires/{id}/operations",
     *     summary="Get all operations for a tire",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="List of operations")
     * )
     */
    public function operations($id)
    {
        return $this->lifecycle($id);
    }
    /**
     * @OA\Get(
     *     path="/api/tires/{id}/inspections",
     *     summary="Get all inspections for a tire",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="List of inspections")
     * )
     */
    public function inspections($id)
    {
        // Get inspections where this tire was inspected (via inspection_items)
        $inspections = \App\Models\Inspection::whereHas('items', function ($query) use ($id) {
            $query->where('tire_id', $id);
        })->with(['items' => function($q) use ($id) {
            $q->where('tire_id', $id);
        }, 'vehicle'])->latest()->paginate(20);
        
        return response()->json($inspections);
    }
}
