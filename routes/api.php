<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login'])->name('login');
// Temporary Debug Route
Route::get('/debug-status', function () {
    try {
        // 1. Check DB Connection
        \DB::connection()->getPdo();
        $dbStatus = "Connected: " . \DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        $dbStatus = "Failed: " . $e->getMessage();
    }

    return response()->json([
        'app_name' => config('app.name'),
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'database_status' => $dbStatus,
        'app_key_exists' => !empty(config('app.key')),
        'storage_writable' => is_writable(storage_path('logs')),
        'env_vars' => [
            'DB_HOST' => config('database.connections.pgsql.host'),
            'APP_URL' => config('app.url'),
        ]
    ]);
});

Route::get('login', function() {
    return response()->json(['message' => 'Unauthenticated.'], 401);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout']);
    Route::get('user', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'user']);
    Route::post('change-password', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'changePassword']);
});

// Stock Management
// Stock Management (Protected by 'view stock' etc)
Route::prefix('stock')->middleware(['auth:sanctum', 'can:view stock'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'dashboard']);
    Route::get('tires/{id}/inspections', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'inspections']);
    Route::get('tires/{id}/label', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'label']);
    Route::get('tires/{id}/history', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'history']);
    Route::post('tires/bulk', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'bulkStore'])->middleware('can:create stock');
    Route::get('tires/{id}/lifecycle', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'lifecycle']);
    Route::get('tires/{id}/operations', [\App\Http\Controllers\Api\V1\Stock\TireController::class, 'operations']);
    Route::apiResource('tires', \App\Http\Controllers\Api\V1\Stock\TireController::class); // Index/Show covered by group. Store/Update needs specific checks if stricter.
    Route::post('movements', [\App\Http\Controllers\Api\V1\Stock\StockMovementController::class, 'store'])->middleware('can:edit stock');
});

// Operations Management
Route::prefix('operations')->middleware(['auth:sanctum', 'can:view operations'])->group(function () {
    // Vehicle Management Extras
    Route::get('vehicles/{id}/inspections', [\App\Http\Controllers\Api\V1\Operations\VehicleController::class, 'inspections']);
    Route::get('vehicles/{id}/timeline', [\App\Http\Controllers\Api\V1\Operations\VehicleController::class, 'timeline']);
    Route::delete('vehicles/{id}/archive', [\App\Http\Controllers\Api\V1\Operations\VehicleController::class, 'archive'])->middleware('can:edit stock');
    Route::post('vehicles/{id}/retire', [\App\Http\Controllers\Api\V1\Operations\VehicleController::class, 'retire'])->middleware('can:edit stock');
    Route::apiResource('vehicles', \App\Http\Controllers\Api\V1\Operations\VehicleController::class);

    // Tire Service Operations (Requires 'perform operations')
    Route::middleware('can:perform operations')->group(function() {
        Route::post('mount', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'mount']);
        Route::post('dismount', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'dismount']);
        Route::post('rotate', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'rotate']);
        Route::post('repair', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'repair']);
        Route::post('replace', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'replace']);
        Route::post('dispose', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'dispose']);
        Route::post('validate-tire', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'validateTire']);
        Route::post('validate-compatibility', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'validateCompatibility']);
        Route::post('assign-tire', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'assignTire']);
        Route::post('upload-photo', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'uploadPhoto']);
        Route::post('add-note', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'addNote']);
    });
    
    // Read operations
    Route::get('positions', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'getPositions']);
    Route::get('{id}', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'show']);
    Route::get('user/{userId}', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'getUserOperations']);
    Route::get('vehicle/{vehicleId}', [\App\Http\Controllers\Api\V1\Operations\TireServiceController::class, 'getVehicleOperations']);
});

// Inspection Management
Route::middleware(['auth:sanctum', 'can:view inspections'])->group(function() {
    Route::prefix('inspection-types')->get('/', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'getTypes']);
    Route::prefix('inspection-checklist')->get('/', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'getChecklist']);

    Route::prefix('inspections')->group(function () {
        Route::post('{id}/assign', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'assign'])->middleware('can:approve inspections');
        Route::post('{id}/schedule', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'schedule'])->middleware('can:approve inspections');
        Route::post('{id}/review', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'review'])->middleware('can:approve inspections');
        Route::post('{id}/approve', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'approve'])->middleware('can:approve inspections');
        Route::post('{id}/reject', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'reject'])->middleware('can:approve inspections');
    });
    
    Route::apiResource('inspections', \App\Http\Controllers\Api\V1\Inspection\InspectionController::class)->only(['index', 'show']);
    Route::post('inspections', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'store'])->middleware('can:perform inspections');
    Route::put('inspections/{id}', [\App\Http\Controllers\Api\V1\Inspection\InspectionController::class, 'update'])->middleware('can:perform inspections');
});

// Alerts
Route::post('alerts/{id}/resolve', [\App\Http\Controllers\Api\V1\Alert\AlertController::class, 'resolve'])->middleware(['auth:sanctum', 'can:edit stock']);
Route::apiResource('alerts', \App\Http\Controllers\Api\V1\Alert\AlertController::class)->only(['index', 'show'])->middleware(['auth:sanctum']);

// Reports
Route::prefix('reports')->middleware(['auth:sanctum', 'can:view reports'])->group(function () {
    Route::get('low-stock', [\App\Http\Controllers\Api\V1\Report\ReportController::class, 'lowStock']);
    Route::get('tire-summary', [\App\Http\Controllers\Api\V1\Report\ReportController::class, 'tireSummary']);
    Route::get('inspection-compliance', [\App\Http\Controllers\Api\V1\Report\ReportController::class, 'inspectionCompliance']);
    Route::get('alerts-summary', [\App\Http\Controllers\Api\V1\Report\ReportController::class, 'alertSummary']);
    Route::get('tire-performance', [\App\Http\Controllers\Api\V1\Report\ReportController::class, 'tirePerformance']);
});

// Admin User Management (RBAC)
Route::middleware(['auth:sanctum', 'role:Administrator'])->prefix('admin')->group(function () {
    Route::apiResource('users', \App\Http\Controllers\Api\V1\Admin\UserController::class);
    Route::apiResource('roles', \App\Http\Controllers\Api\V1\Admin\RoleController::class)->only(['index', 'store', 'destroy']);
});
