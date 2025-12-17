<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reports/low-stock",
     *     summary="Low stock report",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Tires low in stock")
     * )
     */
    public function lowStock()
    {
        // Example: Count tires by size/model where count < threshold
        // Implementing simple count for now
        $lowStock = \App\Models\Tire::select('size', 'brand', 'model', \DB::raw('count(*) as count'))
            ->where('status', 'in_stock')
            ->groupBy('size', 'brand', 'model')
            ->havingRaw('count(*) < ?', [5]) // Threshold 5
            ->get();

        return response()->json($lowStock);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/tire-summary",
     *     summary="Tire status summary",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Summary of tires by status")
     * )
     */
    public function tireSummary()
    {
        $summary = \App\Models\Tire::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        return response()->json($summary);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/inspection-compliance",
     *     summary="Inspection compliance report",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Compliance data")
     * )
     */
    public function inspectionCompliance()
    {
        // Vehicles inspected in last 30 days vs total active vehicles
        $totalVehicles = \App\Models\Vehicle::where('status', 'active')->count();
        
        $inspectedVehicles = \App\Models\Inspection::where('created_at', '>=', now()->subDays(30))
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        return response()->json([
            'total_active_vehicles' => $totalVehicles,
            'inspected_last_30_days' => $inspectedVehicles,
            'compliance_rate' => $totalVehicles > 0 ? round(($inspectedVehicles / $totalVehicles) * 100, 2) : 0
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/alerts-summary",
     *     summary="Alerts summary by severity",
     *     tags={"Reports"},
     *     @OA\Response(response="200", description="Alert statistics")
     * )
     */
    public function alertSummary()
    {
        $alerts = \App\Models\Alert::select('level', 'status', \DB::raw('count(*) as count'))
            ->groupBy('level', 'status')
            ->get();
            
        return response()->json($alerts);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/tire-performance",
     *     summary="Tire Performance Analysis (CPK)",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="Performance metrics per brand/model")
     * )
     */
    public function tirePerformance()
    {
        // 1. Get all tires with operations
        $tires = \App\Models\Tire::with(['movements', 'vehicle', 'operations' => function($q) {
            $q->orderBy('created_at', 'asc');
        }])->get();

        $performance = [];

        foreach ($tires as $tire) {
            $totalDistance = 0;
            $currentMountOdometer = null;
            $repairCost = 0;

            // Calculate Distance from Operations History
            foreach ($tire->operations as $op) {
                if ($op->type === 'mount' || $op->type === 'replace_in') {
                    $currentMountOdometer = $op->odometer;
                } elseif (($op->type === 'dismount' || $op->type === 'replace_out' || $op->type === 'dispose') && $currentMountOdometer !== null) {
                    $diff = $op->odometer - $currentMountOdometer;
                    if ($diff > 0) $totalDistance += $diff;
                    $currentMountOdometer = null;
                } elseif ($op->type === 'repair') {
                    $repairCost += $op->cost;
                }
            }

            // If still mounted, calculate distance until now
            if ($tire->status === 'mounted' && $currentMountOdometer !== null && $tire->vehicle) {
                $diff = $tire->vehicle->odometer - $currentMountOdometer;
                if ($diff > 0) $totalDistance += $diff;
            }

            $totalCost = $tire->cost + $repairCost;
            $cpk = $totalDistance > 0 ? $totalCost / $totalDistance : 0;

            // Aggregate by Brand/Model
            $key = $tire->brand . ' - ' . $tire->model;
            if (!isset($performance[$key])) {
                $performance[$key] = [
                    'brand' => $tire->brand,
                    'model' => $tire->model,
                    'total_tires' => 0,
                    'avg_cpk' => 0,
                    'total_km' => 0,
                    'total_cost' => 0
                ];
            }

            $performance[$key]['total_tires']++;
            $performance[$key]['total_km'] += $totalDistance;
            $performance[$key]['total_cost'] += $totalCost;
        }

        // Calculate Averages
        $results = [];
        foreach ($performance as $key => $data) {
            $data['avg_cpk'] = $data['total_km'] > 0 ? round($data['total_cost'] / $data['total_km'], 4) : 0;
            $results[] = $data;
        }

        // Sort by best CPK (lowest)
        usort($results, function($a, $b) {
            return $a['avg_cpk'] <=> $b['avg_cpk'];
        });

        return response()->json($results);
    }
}
