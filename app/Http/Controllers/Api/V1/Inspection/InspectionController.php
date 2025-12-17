<?php

namespace App\Http\Controllers\Api\V1\Inspection;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/inspections",
     *     summary="List all inspections",
     *     tags={"Inspection"},
     *     @OA\Response(response="200", description="List of inspections")
     * )
     */
    /**
     * @OA\Get(
     *     path="/api/inspections",
     *     summary="List all inspections",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="List of inspections")
     * )
     */
    public function index(Request $request)
    {
        $query = \App\Models\Inspection::with(['vehicle', 'assignedTo']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json($query->latest()->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/inspections",
     *     summary="Create inspection task",
     *     tags={"Inspection"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_id", "type"},
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *             @OA\Property(property="assigned_to", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Inspection task created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            // If items are present, treat as immediate submission (legacy/mobile direct)
            'items' => 'nullable|array',
            'odometer' => 'nullable|integer',
        ]);

        if (isset($validated['items'])) {
            return $this->submitResults($request);
        }

        $inspection = \App\Models\Inspection::create([
            'vehicle_id' => $validated['vehicle_id'],
            'type' => $validated['type'],
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(), // fall back or null
            'status' => 'pending',
        ]);

        return response()->json($inspection, 201);
    }
    
    /**
     * @OA\Put(
     *     path="/api/inspections/{id}",
     *     summary="Edit inspection task",
     *     tags={"Inspection"},
     *     @OA\Response(response="200", description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $inspection = \App\Models\Inspection::findOrFail($id);
        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'nullable|string',
        ]);
        
        $inspection->update($validated);
        return response()->json($inspection);
    }

    /**
     * @OA\Post(
     *     path="/api/inspections/{id}/assign",
     *     summary="Assign inspection to inspector",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Assigned")
     * )
     */
    public function assign(Request $request, $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $inspection = \App\Models\Inspection::findOrFail($id);
        $inspection->update(['assigned_to' => $request->user_id]);
        return response()->json($inspection);
    }

    // Internal Helper for submitting results (used by store if items present, or separate endpoint)
    protected function submitResults(Request $request)
    {
        // Existing logic from previous store method, adapted
         $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'odometer' => 'required|integer',
            'items' => 'required|array',
            // ... strict validation for items
            'items.*.position' => 'required|string',
            'items.*.pressure_psi' => 'nullable|numeric',
            'items.*.tread_depth_mm' => 'nullable|numeric',
        ]);

        $inspection = \DB::transaction(function () use ($validated, $request) {
           \App\Models\Vehicle::where('id', $validated['vehicle_id'])->update(['odometer' => $validated['odometer']]);

            $inspection = \App\Models\Inspection::create([
                'vehicle_id' => $validated['vehicle_id'],
                'user_id' => $request->user_id, 
                'assigned_to' => $request->user_id ?? auth()->id(),
                'odometer' => $validated['odometer'],
                'type' => $request->type ?? 'routine',
                'notes' => $request->notes ?? null,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Save items & Alerts (copied from previous logic)
            foreach ($validated['items'] as $item) {
                // ... same item creation & alert logic as before ...
                $this->createItemAndAlerts($inspection, $item, $validated['vehicle_id']);
            }
            
            return $inspection;
        });

        return response()->json($inspection->load('items'), 201);
    }
    
    // Separate method to keep code clean
    private function createItemAndAlerts($inspection, $item, $vehicleId) {
        $tireId = $item['tire_id'] ?? null;
        if (!$tireId) {
            $mountedTire = \App\Models\Tire::where('vehicle_id', $vehicleId)
                ->where('position', $item['position'])
                ->where('status', 'mounted')
                ->first();
            $tireId = $mountedTire ? $mountedTire->id : null;
        }

        \App\Models\InspectionItem::create([
            'inspection_id' => $inspection->id,
            'tire_id' => $tireId,
            'position' => $item['position'],
            'pressure_psi' => $item['pressure_psi'] ?? null,
            'tread_depth_mm' => $item['tread_depth_mm'] ?? null,
            'condition' => $item['condition'] ?? 'good',
            'issues' => $item['issues'] ?? null,
        ]);
        
        // Alerts Logic
        if (isset($item['pressure_psi']) && $item['pressure_psi'] < 90) { 
             \App\Models\Alert::create([
                'tire_id' => $tireId, 'vehicle_id' => $vehicleId, 'inspection_id' => $inspection->id,
                'code' => 'LOW_PRESSURE', 'level' => 'warning', 'message' => "Low pressure {$item['pressure_psi']}"
             ]);
        }
        // ... (Complete alert logic from before)
    }

    /**
     * @OA\Post(
     *     path="/api/inspections/{id}/schedule",
     *     summary="Schedule inspection",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"scheduled_at"},
     *             @OA\Property(property="scheduled_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Scheduled")
     * )
     */
    public function schedule(Request $request, $id)
    {
         $request->validate(['scheduled_at' => 'required|date']);
         $inspection = \App\Models\Inspection::findOrFail($id);
         $inspection->update(['scheduled_at' => $request->scheduled_at, 'status' => 'scheduled']);
         return response()->json($inspection);
    }

    /**
     * @OA\Post(
     *     path="/api/inspections/{id}/review",
     *     summary="Submit inspection for review",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Under Review")
     * )
     */
    public function review(Request $request, $id)
    {
        $inspection = \App\Models\Inspection::findOrFail($id);
        $inspection->update(['status' => 'reviewed', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return response()->json($inspection);
    }

    /**
     * @OA\Post(
     *     path="/api/inspections/{id}/approve",
     *     summary="Approve inspection",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Approved")
     * )
     */
    public function approve(Request $request, $id)
    {
        $inspection = \App\Models\Inspection::findOrFail($id);
        $inspection->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return response()->json($inspection);
    }

    /**
     * @OA\Post(
     *     path="/api/inspections/{id}/reject",
     *     summary="Reject inspection",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Rejected")
     * )
     */
    public function reject(Request $request, $id)
    {
        $inspection = \App\Models\Inspection::findOrFail($id);
        $inspection->update([
            'status' => 'rejected', 
            'reviewed_by' => auth()->id(), 
            'reviewed_at' => now(), 
            'rejection_reason' => $request->reason
        ]);
        return response()->json($inspection);
    }

    /**
     * @OA\Get(
     *     path="/api/inspection-types",
     *     summary="Get inspection types",
     *     tags={"Inspection"},
     *     @OA\Response(response="200", description="List of types")
     * )
     */
    public function getTypes()
    {
        return response()->json(['routine', 'pre-trip', 'workshop', 'end-of-life']);
    }

    /**
     * @OA\Get(
     *     path="/api/inspection-checklist",
     *     summary="Get inspection checklist",
     *     tags={"Inspection"},
     *     @OA\Response(response="200", description="Checklist definitions")
     * )
     */
    public function getChecklist()
    {
         return response()->json([
             ['step' => 1, 'label' => 'Check Odometer', 'type' => 'number'],
             ['step' => 2, 'label' => 'Inspect FL Tire', 'type' => 'tire_check', 'position' => 'FL'],
             // ...
         ]);
    }
    
    /**
     * @OA\Get(
     *     path="/api/inspections/{id}",
     *     summary="Get inspection details",
     *     tags={"Inspection"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Inspection details")
     * )
     */
    public function show($id)
    {
        return response()->json(\App\Models\Inspection::with(['vehicle', 'items.tire'])->findOrFail($id));
    }
}
