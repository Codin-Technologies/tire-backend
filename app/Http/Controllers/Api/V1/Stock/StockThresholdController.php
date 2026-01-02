<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockThreshold;

class StockThresholdController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/stock/thresholds",
     *     summary="List all stock thresholds",
     *     tags={"Stock"},
     *     @OA\Response(response="200", description="List of thresholds")
     * )
     */
    public function index()
    {
        return response()->json(StockThreshold::all());
    }

    /**
     * @OA\Post(
     *     path="/api/stock/thresholds",
     *     summary="Create or update a stock threshold",
     *     tags={"Stock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand", "model", "size", "min_quantity"},
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="size", type="string"),
     *             @OA\Property(property="min_quantity", type="integer"),
     *             @OA\Property(property="alert_email", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Threshold saved")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'size' => 'required|string',
            'min_quantity' => 'required|integer|min:0',
            'alert_email' => 'nullable|email',
        ]);

        $threshold = StockThreshold::updateOrCreate(
            [
                'brand' => $validated['brand'],
                'model' => $validated['model'],
                'size' => $validated['size'],
            ],
            [
                'min_quantity' => $validated['min_quantity'],
                'alert_email' => $validated['alert_email'] ?? null,
            ]
        );

        return response()->json($threshold);
    }

    /**
     * @OA\Delete(
     *     path="/api/stock/thresholds/{id}",
     *     summary="Delete a threshold",
     *     tags={"Stock"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="204", description="Deleted")
     * )
     */
    public function destroy($id)
    {
        StockThreshold::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
