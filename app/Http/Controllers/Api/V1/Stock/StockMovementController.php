<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/stock/movements",
     *     summary="Record a stock movement (Transfer, Retire, Defect)",
     *     tags={"Stock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tire_id", "type"},
     *             @OA\Property(property="tire_id", type="integer"),
     *             @OA\Property(property="to_warehouse_id", type="integer", description="Required for transfer"),
     *             @OA\Property(property="type", type="string", enum={"transfer", "retire", "defect", "reserve", "mount"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Movement recorded")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'to_warehouse_id' => 'nullable|required_if:type,transfer|exists:warehouses,id',
            'type' => 'required|in:transfer,retire,defect,reserve,mount,available',
            'notes' => 'nullable|string'
        ]);

        $tire = \App\Models\Tire::findOrFail($validated['tire_id']);
        $fromWarehouseId = $tire->warehouse_id;

        // Create Movement Record
        $movement = \App\Models\StockMovement::create([
            'tire_id' => $tire->id,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $validated['type'] === 'transfer' ? $validated['to_warehouse_id'] : $fromWarehouseId,
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
            // 'user_id' => auth()->id()
        ]);

        // Update Tire State
        if ($validated['type'] === 'transfer') {
            $tire->warehouse_id = $validated['to_warehouse_id'];
        } elseif (in_array($validated['type'], ['retire', 'defect', 'reserve', 'mount', 'available'])) {
            // Map movement type to status if applicable
            $statusMap = [
                'retire' => 'retired',
                'defect' => 'defective',
                'reserve' => 'reserved',
                'mount' => 'mounted',
                'available' => 'available'
            ];
            if (isset($statusMap[$validated['type']])) {
                $tire->status = $statusMap[$validated['type']];
            }
        }
        
        $tire->save();

        return response()->json($movement, 201);
    }
}
