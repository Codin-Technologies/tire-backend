<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/roles",
     *     summary="List all roles",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Role list")
     * )
     */
    public function index()
    {
        return response()->json(\Spatie\Permission\Models\Role::all());
    }

    /**
     * @OA\Post(
     *     path="/api/admin/roles",
     *     summary="Create new role",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Regional Manager")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Role created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        $role = \Spatie\Permission\Models\Role::create(['name' => $validated['name']]);

        $this->logAction('created', 'Role', $role->id, $validated);

        return response()->json($role, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/roles/{id}",
     *     summary="Delete role",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Role deleted")
     * )
     */
    public function destroy($id)
    {
        $role = \Spatie\Permission\Models\Role::findOrFail($id);
        
        // Prevent deleting critical system roles if desired, e.g. Administrator
        if ($role->name === 'Administrator') {
            return response()->json(['message' => 'Cannot delete Administrator role'], 403);
        }

        $role->delete();

        $this->logAction('deleted', 'Role', $id, ['name' => $role->name]);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    private function logAction($action, $type, $id, $changes)
    {
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $type,
            'target_id' => $id,
            'changes' => json_encode($changes),
            'ip_address' => request()->ip()
        ]);
    }
}
