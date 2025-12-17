<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="List all users",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="User list")
     * )
     */
    public function index()
    {
        return response()->json(\App\Models\User::with('roles')->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create new user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *     @OA\Response(response="201", description="User created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'nullable|string|min:8', // Make nullable to allow auto-generation
            'role' => 'required|exists:roles,name'
        ]);

        // Generate password if not provided
        $plainPassword = $request->password ?? \Illuminate\Support\Str::random(10);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($plainPassword),
        ]);

        $user->assignRole($validated['role']);

        $this->logAction('created', 'User', $user->id, $validated);
        
        // Send Welcome Email
        try {
             \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UserCreated($user, $plainPassword));
        } catch (\Exception $e) {
            // Log email failure but don't fail the request
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        return response()->json($user->load('roles'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Update user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="User updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|exists:roles,name'
        ]);

        $oldData = $user->toArray();
        $user->update($request->only(['name', 'email']));

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        $this->logAction('updated', 'User', $user->id, ['old' => $oldData, 'new' => $validated]);

        return response()->json($user->load('roles'));
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Delete user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="User deactivated")
     * )
     */
    public function destroy($id)
    {
        // We actually want deactivation, but simple delete for now based on standard CRUD
        // Or specific 'deactivate' flag if column existed. User model uses standard delete.
        $user = \App\Models\User::findOrFail($id);
        $user->delete();
        
        $this->logAction('deleted', 'User', $id, []);
        
        return response()->json(['message' => 'User deleted successfully']);
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
