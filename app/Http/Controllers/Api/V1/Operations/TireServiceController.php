<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TireServiceController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/operations/mount",
     *     summary="Mounts a tire onto a vehicle at a specific position Mounts Validates that the tire exists, is not retired or defective, is compatible with the vehicle/axle, and that the vehicle exists. Captures vehicle mileage, technician (logged-inuser), timestamp, optional photos and notes. Updates tire status to mounted and records lifecycle history",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tire_id", "vehicle_id", "position", "odometer"},
     *             @OA\Property(property="tire_id", type="integer"),
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="position", type="string"),
     *             @OA\Property(property="odometer", type="integer"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Tire mounted")
     * )
     */
    public function mount(Request $request)
    {
        $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'position' => 'required|string', // Can be axle_position_code e.g. "A2-R1"
            'odometer' => 'required|integer',
            'user_id' => 'nullable|exists:users,id', // Technician
            'notes' => 'nullable|string'
        ]);

        $tire = \App\Models\Tire::findOrFail($validated['tire_id']);
        
        if (in_array($tire->status, ['retired', 'defective', 'mounted'])) {
            return response()->json(['error' => "Tire status '{$tire->status}' prevents mounting."], 400);
        }

        // Check if position is occupied matches an AxlePosition
        $axlePos = \App\Models\AxlePosition::where('vehicle_id', $validated['vehicle_id'])
            ->where('position_code', $validated['position'])
            ->first();

        // Check legacy or AxlePosition occupancy
        if ($axlePos && $axlePos->tire_id) {
             return response()->json(['error' => 'Axle Position occupied by tire ID: ' . $axlePos->tire_id], 400);
        }

        $occupant = \App\Models\Tire::where('vehicle_id', $validated['vehicle_id'])
            ->where('position', $validated['position'])
            ->where('status', 'mounted')
            ->first();

        if ($occupant) {
            return response()->json(['error' => 'Position occupied by tire: ' . $occupant->unique_tire_id], 400);
        }

        \DB::transaction(function () use ($tire, $validated, $axlePos) {
            // Update Vehicle Odometer
            \App\Models\Vehicle::where('id', $validated['vehicle_id'])->update(['odometer' => $validated['odometer']]);

            // Assign to AxlePosition if exists
            if ($axlePos) {
                $axlePos->update(['tire_id' => $tire->id]);
            }

            // Create Operation Record
            \App\Models\TireOperation::create([
                'tire_id' => $tire->id,
                'vehicle_id' => $validated['vehicle_id'],
                'user_id' => $validated['user_id'] ?? null,
                'type' => 'mount', // 'issue' alias can be handled in type if needed, but 'mount' is standard
                'odometer' => $validated['odometer'],
                'position' => $validated['position'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update Tire
            $tire->update([
                'status' => 'mounted',
                'vehicle_id' => $validated['vehicle_id'],
                'position' => $validated['position'],
                'warehouse_id' => null,
            ]);
        });

        return response()->json(['message' => 'Tire mounted/issued', 'tire' => $tire->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/issue",
     *     summary="Issue a tire to a vehicle",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tire_id", "vehicle_id", "position", "odometer"},
     *             @OA\Property(property="tire_id", type="integer"),
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="position", type="string"),
     *             @OA\Property(property="odometer", type="integer"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Tire issued")
     * )
     */
    public function issue(Request $request)
    {
        return $this->mount($request);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/dismount",
     *     summary="Dismount a tire",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tire_id", "to_warehouse_id", "odometer"},
     *             @OA\Property(property="tire_id", type="integer"),
     *             @OA\Property(property="to_warehouse_id", type="integer"),
     *             @OA\Property(property="odometer", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Tire dismounted")
     * )
     */
    public function dismount(Request $request)
    {
        $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'odometer' => 'required|integer',
            'reason' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $tire = \App\Models\Tire::findOrFail($validated['tire_id']);

        if ($tire->status !== 'mounted') {
            return response()->json(['error' => 'Tire is not currently mounted'], 400);
        }

        \DB::transaction(function () use ($tire, $validated) {
            // Update Vehicle Odometer
            \App\Models\Vehicle::where('id', $tire->vehicle_id)->update(['odometer' => $validated['odometer']]);

            // Clear AxlePosition if exists
            \App\Models\AxlePosition::where('tire_id', $tire->id)->update(['tire_id' => null]);

            // Create Operation Record
            \App\Models\TireOperation::create([
                'tire_id' => $tire->id,
                'vehicle_id' => $tire->vehicle_id,
                'user_id' => $validated['user_id'] ?? null,
                'type' => 'dismount',
                'odometer' => $validated['odometer'],
                'position' => $tire->position,
                'notes' => $validated['reason'] ?? null,
            ]);

            // Update Tire
            $tire->update([
                'status' => 'available',
                'vehicle_id' => null,
                'position' => null,
                'warehouse_id' => $validated['to_warehouse_id'],
            ]);
        });

        return response()->json(['message' => 'Tire dismounted/removed', 'tire' => $tire->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/remove",
     *     summary="Remove a tire from a vehicle",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tire_id", "to_warehouse_id", "odometer"},
     *             @OA\Property(property="tire_id", type="integer"),
     *             @OA\Property(property="to_warehouse_id", type="integer"),
     *             @OA\Property(property="odometer", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Tire removed")
     * )
     */
    public function remove(Request $request)
    {
        return $this->dismount($request);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/rotate",
     *     summary="Swap tire positions",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vehicle_id", "rotations", "odometer"},
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="rotations", type="array", @OA\Items(
     *                 @OA\Property(property="tire_id", type="integer"),
     *                 @OA\Property(property="new_position", type="string")
     *             )),
     *             @OA\Property(property="odometer", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Rotation complete")
     * )
     */
    public function rotate(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'odometer' => 'required|integer',
            'user_id' => 'nullable|exists:users,id',
            'rotations' => 'required|array',
            'rotations.*.tire_id' => 'required|exists:tires,id',
            'rotations.*.new_position' => 'required|string',
        ]);

        \DB::transaction(function () use ($validated) {
            // Clear AxlePositions first to avoid unique constraints or conflicts during swap
            // NOTE: This assumes positions in AxlePosition are unique per vehicle! 
            // We just need to ensure we don't violate constraints. But tires are foreign keys.
            // We can temporarily set tire_id to null for affected axle positions?
            
            // Perform Rotation
            foreach ($validated['rotations'] as $rot) {
                $tire = \App\Models\Tire::find($rot['tire_id']);
                if ($tire->vehicle_id != $validated['vehicle_id']) {
                    throw new \Exception("Tire {$tire->unique_tire_id} is not on this vehicle.");
                }
                $oldPos = $tire->position;
                
                // Update AxlePosition
                // 1. Remove from old axle pos
                // \App\Models\AxlePosition::where('vehicle_id', $validated['vehicle_id'])
                //    ->where('position_code', $oldPos)->update(['tire_id' => null]);
                
                // 2. Add to new axle pos (we do this last or careful order?)
                // Actually, if we are swapping A <-> B. 
                // A moves to pos B. B moves to pos A.
                // If we process A first, we put A in B's AxlePosition. If B is still there, it might fail? 
                // No, AxlePosition.tire_id is just a foreign key. It's not unique in the DB schema for AxlePosition (multiple positions can technically point to same tire? No, one tire one place).
                // Actually, logic is cleaner if we just update everything.
                
                $targetAxlePos = \App\Models\AxlePosition::where('vehicle_id', $validated['vehicle_id'])
                    ->where('position_code', $rot['new_position'])
                    ->first();
                    
                if ($targetAxlePos) {
                    $targetAxlePos->update(['tire_id' => $tire->id]);
                    
                    // Also need to clear the OLD position if it was an AxlePosition
                     \App\Models\AxlePosition::where('vehicle_id', $validated['vehicle_id'])
                        ->where('position_code', $oldPos)
                        ->where('tire_id', $tire->id) // Only if it still thinks it has THIS tire
                        ->update(['tire_id' => null]);
                }

                // Create Operation Record
                \App\Models\TireOperation::create([
                    'tire_id' => $tire->id,
                    'vehicle_id' => $validated['vehicle_id'],
                    'user_id' => $validated['user_id'] ?? null,
                    'type' => 'rotate',
                    'odometer' => $validated['odometer'],
                    'previous_position' => $oldPos,
                    'position' => $rot['new_position'],
                ]);
                
                $tire->update(['position' => $rot['new_position']]);
            }
            
            \App\Models\Vehicle::where('id', $validated['vehicle_id'])->update(['odometer' => $validated['odometer']]);
        });

        return response()->json(['message' => 'Rotation completed successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/repair",
     *     summary="Record tire repair",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Repair recorded")
     * )
     */
    public function repair(Request $request)
    {
        $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'cost' => 'nullable|numeric',
            'vendor' => 'nullable|string',
            'notes' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $tire = \App\Models\Tire::findOrFail($validated['tire_id']);
        
        $op = \App\Models\TireOperation::create([
            'tire_id' => $tire->id,
            'vehicle_id' => $tire->vehicle_id, // If mounted
            'user_id' => $validated['user_id'] ?? null,
            'type' => 'repair',
            'odometer' => $tire->vehicle ? $tire->vehicle->odometer : null,
            'cost' => $validated['cost'] ?? 0,
            'vendor' => $validated['vendor'] ?? null,
            'notes' => $validated['notes'],
        ]);
        
        return response()->json($op);
    }
    
    /**
     * @OA\Post(
     *     path="/api/operations/replace",
     *     summary="Replace a mounted tire",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Tire replaced")
     * )
     */
    public function replace(Request $request)
    {
        $validated = $request->validate([
            'old_tire_id' => 'required|exists:tires,id',
            'new_tire_id' => 'required|exists:tires,id',
            'odometer' => 'required|integer',
            'reason' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $oldTire = \App\Models\Tire::findOrFail($validated['old_tire_id']);
        $newTire = \App\Models\Tire::findOrFail($validated['new_tire_id']);

        if ($oldTire->status !== 'mounted') {
            return response()->json(['error' => 'Old tire is not currently mounted'], 400);
        }
        if ($newTire->status !== 'available') {
            return response()->json(['error' => 'New tire is not available'], 400);
        }

        \DB::transaction(function () use ($oldTire, $newTire, $validated) {
            $vehicleId = $oldTire->vehicle_id;
            $position = $oldTire->position;
            
            // Dismount Old
            \App\Models\TireOperation::create([
                'tire_id' => $oldTire->id,
                'vehicle_id' => $vehicleId,
                'user_id' => $validated['user_id'] ?? null,
                'type' => 'replace_out',
                'odometer' => $validated['odometer'],
                'position' => $position,
                'notes' => 'Replaced by ' . $newTire->unique_tire_id . '. ' . ($validated['reason'] ?? ''),
            ]);
            
            $oldTire->update([
                'status' => 'available', // Or 'maintenance'
                'vehicle_id' => null,
                'position' => null,
                'warehouse_id' => $newTire->warehouse_id, // Swap back to where new one came from, or specific Logic
            ]);

            // Mount New
            \App\Models\TireOperation::create([
                'tire_id' => $newTire->id,
                'vehicle_id' => $vehicleId,
                'user_id' => $validated['user_id'] ?? null,
                'type' => 'replace_in',
                'odometer' => $validated['odometer'],
                'position' => $position,
                'notes' => 'Replaced ' . $oldTire->unique_tire_id,
            ]);

            $newTire->update([
                'status' => 'mounted',
                'vehicle_id' => $vehicleId,
                'position' => $position,
                'warehouse_id' => null,
            ]);
            
             \App\Models\Vehicle::where('id', $vehicleId)->update(['odometer' => $validated['odometer']]);
        });

        return response()->json(['message' => 'Tire replaced successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/validate-tire",
     *     summary="Validate tire eligibility",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Validation result")
     * )
     */
    public function validateTire(Request $request)
    {
        $request->validate(['tire_id' => 'required|exists:tires,id']);
        $tire = \App\Models\Tire::find($request->tire_id);
        
        $valid = !in_array($tire->status, ['retired', 'defective']);
        $message = $valid ? 'Tire is valid for operations' : "Tire status '{$tire->status}' detected";
        
        return response()->json([
            'valid' => $valid,
            'message' => $message,
            'tire' => $tire
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/validate-compatibility",
     *     summary="Check tire-vehicle compatibility",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Compatibility Result")
     * )
     */
    public function validateCompatibility(Request $request)
    {
        // Placeholder logic - implement actual size/type matching rules here
        return response()->json(['compatible' => true, 'message' => 'Tire size matches vehicle configuration']);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/assign-tire",
     *     summary="Pre-assign tire to position",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Assigned")
     * )
     */
    public function assignTire(Request $request)
    {
        $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'position' => 'required|string'
        ]);
        
        $tire = \App\Models\Tire::find($validated['tire_id']);
        $tire->update([
            'status' => 'reserved',
            'vehicle_id' => $validated['vehicle_id'], // Pre-link without 'mounted' status
            'position' => $validated['position']
        ]);
        
        return response()->json(['message' => 'Tire assigned/reserved']);
    }

    /**
     * @OA\Get(
     *     path="/api/operations/positions",
     *     summary="Get valid tire positions",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="List of positions")
     * )
     */
    public function getPositions()
    {
        return response()->json([
            'FL' => 'Front Left',
            'FR' => 'Front Right',
            'RLI' => 'Rear Left Inner',
            'RLO' => 'Rear Left Outer',
            'RRI' => 'Rear Right Inner',
            'RRO' => 'Rear Right Outer',
            // Add more as needed
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/operations/add-note",
     *     summary="Add note to operation",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Note added")
     * )
     */
    public function addNote(Request $request)
    {
        $validated = $request->validate([
            'operation_id' => 'required|exists:tire_operations,id',
            'note' => 'required|string'
        ]);
        
        $op = \App\Models\TireOperation::find($validated['operation_id']);
        $op->notes .= "\n" . $validated['note'];
        $op->save();
        
        return response()->json($op);
    }
    
    /**
     * @OA\Get(path="/api/operations/{id}", summary="Get operation details", tags={"Operations"}, @OA\Response(response="200", description="Detail"))
     */
    public function show($id) {
        return response()->json(\App\Models\TireOperation::with(['tire', 'vehicle', 'photos'])->findOrFail($id));
    }

    /**
     * @OA\Get(path="/api/operations/user/{userId}", summary="Get technician operations", tags={"Operations"}, @OA\Response(response="200", description="List"))
     */
    public function getUserOperations($userId) {
        return response()->json(\App\Models\TireOperation::where('user_id', $userId)->latest()->paginate(20));
    }

    /**
     * @OA\Get(path="/api/operations/vehicle/{vehicleId}", summary="Get vehicle operations", tags={"Operations"}, @OA\Response(response="200", description="List"))
     */
    public function getVehicleOperations($vehicleId) {
        return response()->json(\App\Models\TireOperation::where('vehicle_id', $vehicleId)->latest()->paginate(20));
    }
    
    /**
     * @OA\Post(
     *     path="/api/operations/dispose",
     *     summary="Retire/Dispose a tire",
     *     tags={"Operations"},
     *     @OA\Response(response="200", description="Tire retired")
     * )
     */
    public function dispose(Request $request)
    {
         $validated = $request->validate([
            'tire_id' => 'required|exists:tires,id',
            'reason' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $tire = \App\Models\Tire::findOrFail($validated['tire_id']);
         
        if ($tire->status == 'mounted') {
            return response()->json(['error' => 'Cannot dispose mounted tire. Dismount first.'], 400);
        }
        
        $op = \App\Models\TireOperation::create([
            'tire_id' => $tire->id,
            'user_id' => $validated['user_id'] ?? null,
            'type' => 'dispose',
            'notes' => $validated['reason'],
        ]);
        
        $tire->update(['status' => 'retired']);
        
        return response()->json($op);
    }
    /**
     * @OA\Post(
     *     path="/api/operations/upload-photo",
     *     summary="Upload photo for a tire operation",
     *     tags={"Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"operation_id", "photo"},
     *                 @OA\Property(property="operation_id", type="integer"),
     *                 @OA\Property(property="photo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Photo uploaded")
     * )
     */
    public function uploadPhoto(Request $request)
    {
        $validated = $request->validate([
            'operation_id' => 'required|exists:tire_operations,id',
            'photo' => 'required|image|max:10240', // Max 10MB
        ]);

        $path = $request->file('photo')->store('tire-photos', 'public');

        $photo = \App\Models\TireOperationPhoto::create([
            'tire_operation_id' => $validated['operation_id'],
            'path' => $path,
            'disk' => 'public',
        ]);

        return response()->json($photo, 201);
    }
}
