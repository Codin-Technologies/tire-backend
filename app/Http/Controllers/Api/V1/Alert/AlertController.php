<?php

namespace App\Http\Controllers\Api\V1\Alert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/alerts",
     *     summary="List all alerts",
     *     tags={"Alerts"},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="List of alerts")
     * )
     */
    public function index(Request $request)
    {
        $query = \App\Models\Alert::with(['tire', 'vehicle']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to open alerts
            $query->where('status', 'open');
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * @OA\Get(
     *     path="/api/alerts/{id}",
     *     summary="Get alert details",
     *     tags={"Alerts"},
     *     @OA\Response(response="200", description="Alert details")
     * )
     */
    public function show($id)
    {
        return response()->json(\App\Models\Alert::with(['tire', 'vehicle', 'inspection'])->findOrFail($id));
    }

    /**
     * @OA\Post(
     *     path="/api/alerts/{id}/resolve",
     *     summary="Resolve an alert",
     *     tags={"Alerts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string", description="Resolution notes")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Alert resolved")
     * )
     */
    public function resolve(Request $request, $id)
    {
        $alert = \App\Models\Alert::findOrFail($id);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $validated['notes'] ?? null,
            'resolved_by' => $validated['user_id'] ?? null
        ]);

        return response()->json(['message' => 'Alert resolved', 'alert' => $alert]);
    }
}
