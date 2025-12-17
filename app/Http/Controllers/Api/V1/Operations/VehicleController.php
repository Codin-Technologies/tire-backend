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
}
