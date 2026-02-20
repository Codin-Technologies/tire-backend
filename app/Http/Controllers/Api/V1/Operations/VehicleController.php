<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/operations/vehicles",
     *     summary="List all vehicles",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="List of vehicles")
     * )
     */
    public function index(Request $request)
    {
        return response()->json(\App\Models\Vehicle::with('tires')->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/operations/vehicles",
     *     summary="Register a new vehicle",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"registration_number", "model", "type"},
     *             @OA\Property(property="registration_number", type="string"),
     *             @OA\Property(property="fleet_number", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="axle_config", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Vehicle created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|unique:vehicles',
            'fleet_number' => 'nullable|string',
            'model' => 'required|string',
            'type' => 'required|string',
            'axle_config' => 'nullable|integer',
        ]);

        $vehicle = \App\Models\Vehicle::create($validated);
        return response()->json($vehicle, 201);
    }
    /**
     * @OA\Get(
     *     path="/api/operations/vehicles/{id}",
     *     summary="Get vehicle details",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Vehicle details with mounted tires")
     * )
     */
    public function show($id)
    {
        return response()->json(\App\Models\Vehicle::with('tires')->findOrFail($id));
    }

    /**
     * @OA\Get(
     *     path="/api/operations/vehicles/{id}/timeline",
     *     summary="Get vehicle operation timeline",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Vehicle operations timeline")
     * )
     */
    public function timeline($id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        
        $operations = \App\Models\TireOperation::where('vehicle_id', $vehicle->id)
            ->with(['tire', 'user'])
            ->latest()
            ->paginate(20);
            
        return response()->json($operations);
    }
    /**
     * @OA\Put(
     *     path="/api/operations/vehicles/{id}",
     *     summary="Update vehicle details",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="fleet_number", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Vehicle updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        
        if ($vehicle->status === 'retired') {
            return response()->json(['error' => 'Vehicle is retired and cannot be edited'], 400);
        }

        $validated = $request->validate([
            'model' => 'sometimes|required|string',
            'fleet_number' => 'nullable|string',
            'type' => 'sometimes|required|string',
            'axle_config' => 'sometimes|integer',
        ]);
        
        $vehicle->update($validated);
        return response()->json($vehicle);
    }

    /**
     * @OA\Delete(
     *     path="/api/operations/vehicles/{id}/archive",
     *     summary="Archive a vehicle",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Vehicle archived")
     * )
     */
    public function archive($id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        $vehicle->update(['archived_at' => now(), 'status' => 'archived']);
        return response()->json(['message' => 'Vehicle archived']);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/vehicles/{id}/retire",
     *     summary="Retire a vehicle",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Vehicle retired")
     * )
     */
    public function retire($id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        // Ensure no mounted tires or handle them - keeping mostly simple for request
        $vehicle->update(['status' => 'retired']);
        return response()->json(['message' => 'Vehicle retired permanently']);
    }
    /**
     * @OA\Get(
     *     path="/api/vehicles/{id}/inspections",
     *     summary="Get all inspections for a vehicle",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="List of inspections")
     * )
     */
    public function inspections($id)
    {
        $inspections = \App\Models\Inspection::where('vehicle_id', $id)
            ->with(['items', 'assignedTo', 'reviewer'])
            ->latest()
            ->paginate(20);
            
        return response()->json($inspections);
    }

    /**
     * @OA\Get(
     *     path="/api/operations/vehicles/{id}/axle-configuration",
     *     summary="Get vehicle axle configuration",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Axle configuration details")
     * )
     */
    public function getAxleConfiguration($id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        $positions = $vehicle->axlePositions()->with('tire.sku')->orderBy('axle_number')->orderBy('position_code')->get();
        return response()->json(['vehicle_id' => $id, 'positions' => $positions]);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/vehicles/{id}/axle-configuration",
     *     summary="Update vehicle axle configuration",
     *     tags={"Operations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"positions"},
     *             @OA\Property(property="positions", type="array", @OA\Items(
     *                 @OA\Property(property="position_code", type="string"),
     *                 @OA\Property(property="axle_number", type="integer"),
     *                 @OA\Property(property="side", type="string", enum={"L", "R"}),
     *                 @OA\Property(property="tire_type_requirement", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response="200", description="Configuration updated")
     * )
     */
    public function updateAxleConfiguration(Request $request, $id)
    {
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        
        $validated = $request->validate([
            'positions' => 'required|array',
            'positions.*.position_code' => 'required|string',
            'positions.*.axle_number' => 'required|integer',
            'positions.*.side' => 'required|in:L,R',
            'positions.*.tire_type_requirement' => 'nullable|in:STEER,DRIVE,TRAILER,ALL_POSITION',
        ]);

        \DB::transaction(function () use ($vehicle, $validated) {
            // We do a sync-like operation. 
            // 1. Identify existing codes to update
            // 2. Add new ones
            // 3. Remove ones not in list (careful if tires attached!)
            
            $providedCodes = collect($validated['positions'])->pluck('position_code')->toArray();
            
            // Check for tires on positions to be deleted
            $toDelete = $vehicle->axlePositions()->whereNotIn('position_code', $providedCodes)->get();
            foreach ($toDelete as $pos) {
                if ($pos->tire_id) {
                    throw new \Exception("Cannot remove position {$pos->position_code} because it has a tire mounted.");
                }
                $pos->delete();
            }
            
            foreach ($validated['positions'] as $posData) {
                $vehicle->axlePositions()->updateOrCreate(
                    ['position_code' => $posData['position_code']],
                    [
                        'axle_number' => $posData['axle_number'],
                        'side' => $posData['side'],
                        'tire_type_requirement' => $posData['tire_type_requirement'] ?? null,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Axle configuration updated', 'positions' => $vehicle->axlePositions]);
    }
}
