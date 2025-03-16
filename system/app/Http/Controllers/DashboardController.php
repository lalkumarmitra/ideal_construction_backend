<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function getAnalytics(Request $request)
    {
        return $this->tryCatchWrapper(function() use ($request) {
            // Check authorization
            if (Auth::user()->role->type !== 'admin') {
                throw new \Exception('Unauthorized Request', 403);
            }
            
            // Validate request
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            // Create base query
            $query = Transaction::query()
                ->whereBetween('loading_date', [$validated['from_date'], $validated['to_date']]);

            // Get all analytics data
            return [
                'message' => 'Dashboard analytics fetched successfully',
                'data' => [
                    // Overall statistics
                    'overall_stats' => $this->getOverallStats($query),
                    
                    // Top resources
                    'top_vehicles' => $this->getTopVehicles($query),
                    'top_drivers' => $this->getTopDrivers($query),
                    'products_by_value' => $this->getProductsByValue($query),
                    'products_by_quantity' => $this->getProductsByUnloadingQuantity($query),
                    
                    // Client analytics
                    'loading_points_by_quantity' => $this->getLoadingPointsByQuantity($query),
                    'unloading_points_by_quantity' => $this->getUnloadingPointsByQuantity($query),
                    'loading_points_by_price' => $this->getLoadingPointsByPrice($query),
                    'unloading_points_by_price' => $this->getUnloadingPointsByPrice($query),
                    
                    // Additional analytics
                    'summary' => $this->getSummaryStats($query),
                    'transaction_types' => $this->getTransactionTypeStats($query),
                    'daily_transactions' => $this->getDailyTransactionStats($query),
                    'quantity_discrepancies' => $this->getQuantityDiscrepancies($query),
                    'inactive_resources' => $this->getInactiveResources($query),
                ]
            ];
        });
    }

    private function getTopVehicles($query){
        // Clone the query to avoid modifying the original
        $vehicleQuery = clone $query;
        
        return $vehicleQuery->select(
                'loading_vehicle_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('loadingVehicle:id,number,type')
            ->whereNotNull('loading_vehicle_id')
            ->groupBy('loading_vehicle_id')
            ->orderByDesc('avg_expense')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'vehicle' => $item->loadingVehicle,
                    'transaction_count' => $item->transaction_count,
                    'average_expense' => round($item->avg_expense, 2)
                ];
            });
    }

    private function getTopDrivers($query){
        // Clone the query to avoid modifying the original
        $driverQuery = clone $query;
        
        return $driverQuery->select(
                'loading_driver_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(transport_expense) as total_expense'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('loadingDriver:id,name')
            ->whereNotNull('loading_driver_id')
            ->groupBy('loading_driver_id')
            ->orderByDesc('avg_expense')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'driver' => $item->loadingDriver,
                    'transaction_count' => $item->transaction_count,
                    'total_expense' => round($item->total_expense, 2),
                    'average_expense' => round($item->avg_expense, 2)
                ];
            });
    }

    private function getProductsByValue($query) {
        // Clone the query to avoid modifying the original
        $productQuery = clone $query;
        
        return $productQuery->select(
                'product_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_quantity) as total_loading_quantity'),
                DB::raw('SUM(unloading_quantity) as total_unloading_quantity'),
                DB::raw('SUM(unloading_rate * unloading_quantity) as total_value'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('product:id,name')
            ->whereNotNull('product_id')
            ->whereNotNull('unloading_rate')
            ->whereNotNull('unloading_quantity')
            ->groupBy('product_id')
            ->orderByDesc(DB::raw('SUM(unloading_rate * unloading_quantity)'))
            ->get()
            ->map(function($item) {
                return [
                    'product' => $item->product,
                    'transaction_count' => $item->transaction_count,
                    'total_loading_quantity' => $item->total_loading_quantity,
                    'total_unloading_quantity' => $item->total_unloading_quantity,
                    'total_value' => round($item->total_value, 2),
                    'average_expense' => round($item->avg_expense, 2)
                ];
            });
    }
    
    private function getProductsByUnloadingQuantity($query) {
        // Clone the query to avoid modifying the original
        $productQuery = clone $query;
        
        return $productQuery->select(
                'product_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_quantity) as total_loading_quantity'),
                DB::raw('SUM(unloading_quantity) as total_unloading_quantity'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('product:id,name')
            ->whereNotNull('product_id')
            ->whereNotNull('unloading_quantity')
            ->groupBy('product_id')
            ->orderByDesc(DB::raw('SUM(unloading_quantity)'))
            ->get()
            ->map(function($item) {
                return [
                    'product' => $item->product,
                    'transaction_count' => $item->transaction_count,
                    'total_loading_quantity' => $item->total_loading_quantity,
                    'total_unloading_quantity' => $item->total_unloading_quantity,
                    'average_expense' => round($item->avg_expense, 2)
                ];
            });
    }

    private function getLoadingPointsByQuantity($query) {
        // Clone the query to avoid modifying the original
        $loadingQuery = clone $query;
        
        return $loadingQuery->select(
                'loading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_quantity) as total_quantity')
            )
            ->with('loadingPoint:id,name')
            ->whereNotNull('loading_point_id')
            ->groupBy('loading_point_id')
            ->orderByDesc('total_quantity')
            ->get()
            ->map(function($item) {
                return [
                    'client' => $item->loadingPoint,
                    'transaction_count' => $item->transaction_count,
                    'total_quantity' => $item->total_quantity
                ];
            });
    }

    private function getUnloadingPointsByQuantity($query){
        // Clone the query to avoid modifying the original
        $unloadingQuery = clone $query;
        
        return $unloadingQuery->select(
                'unloading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(unloading_quantity) as total_quantity')
            )
            ->with('unloadingPoint:id,name')
            ->whereNotNull('unloading_point_id')
            ->groupBy('unloading_point_id')
            ->orderByDesc('total_quantity')
            ->get()
            ->map(function($item) {
                return [
                    'client' => $item->unloadingPoint,
                    'transaction_count' => $item->transaction_count,
                    'total_quantity' => $item->total_quantity
                ];
            });
    }

    private function getLoadingPointsByPrice($query){
        // Clone the query to avoid modifying the original
        $loadingPriceQuery = clone $query;
        
        return $loadingPriceQuery->select(
                'loading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_rate * loading_quantity) as total_price')
            )
            ->with('loadingPoint:id,name')
            ->whereNotNull('loading_point_id')
            ->groupBy('loading_point_id')
            ->orderByDesc('total_price')
            ->get()
            ->map(function($item) {
                return [
                    'client' => $item->loadingPoint,
                    'transaction_count' => $item->transaction_count,
                    'total_price' => round($item->total_price, 2)
                ];
            });
    }

    private function getUnloadingPointsByPrice($query){
        // Clone the query to avoid modifying the original
        $unloadingPriceQuery = clone $query;
        
        return $unloadingPriceQuery->select(
                'unloading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(unloading_rate * unloading_quantity) as total_price')
            )
            ->with('unloadingPoint:id,name')
            ->whereNotNull('unloading_point_id')
            ->groupBy('unloading_point_id')
            ->orderByDesc('total_price')
            ->get()
            ->map(function($item) {
                return [
                    'client' => $item->unloadingPoint,
                    'transaction_count' => $item->transaction_count,
                    'total_price' => round($item->total_price, 2)
                ];
            });
    }

    private function getSummaryStats($query){
        // Clone the query to avoid modifying the original
        $summaryQuery = clone $query;
        
        return [
            'total_transactions' => $summaryQuery->count(),
            'total_transport_expense' => $summaryQuery->sum('transport_expense'),
            'average_transport_expense' => round($summaryQuery->avg('transport_expense'), 2),
            'total_loading_quantity' => $summaryQuery->sum('loading_quantity'),
            'total_unloading_quantity' => $summaryQuery->sum('unloading_quantity'),
            'total_loading_value' => round($summaryQuery->selectRaw('SUM(loading_rate * loading_quantity) as total')->value('total'), 2),
            'total_unloading_value' => round($summaryQuery->selectRaw('SUM(unloading_rate * unloading_quantity) as total')->value('total'), 2),
        ];
    }

    private function getTransactionTypeStats($query){
        // Clone the query to avoid modifying the original
        $typeQuery = clone $query;
        
        return $typeQuery->select('txn_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(transport_expense) as total_expense')
            ->selectRaw('AVG(transport_expense) as avg_expense')
            ->groupBy('txn_type')
            ->get()
            ->map(function($item) {
                return [
                    'type' => $item->txn_type,
                    'count' => $item->count,
                    'total_expense' => round($item->total_expense, 2),
                    'avg_expense' => round($item->avg_expense, 2)
                ];
            });
    }

    private function getDailyTransactionStats($query){
        // Clone the query to avoid modifying the original
        $dailyQuery = clone $query;
        
        return $dailyQuery->select(
                DB::raw('DATE(loading_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(transport_expense) as total_expense'),
                DB::raw('SUM(loading_quantity) as total_loading'),
                DB::raw('SUM(unloading_quantity) as total_unloading')
            )
            ->groupBy(DB::raw('DATE(loading_date)'))
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'transaction_count' => $item->transaction_count,
                    'total_expense' => round($item->total_expense, 2),
                    'total_loading' => $item->total_loading,
                    'total_unloading' => $item->total_unloading
                ];
            });
    }

    private function getQuantityDiscrepancies($query)
    {
        // Clone the query to avoid modifying the original
        $discrepancyQuery = clone $query;
        
        return $discrepancyQuery->select(
                'id',
                'product_id',
                'loading_quantity',
                'unloading_quantity',
                DB::raw('ABS(loading_quantity - unloading_quantity) as difference')
            )
            ->with('product:id,name')
            ->whereRaw('ABS(loading_quantity - unloading_quantity) > 0')
            ->orderByRaw('ABS(loading_quantity - unloading_quantity) DESC')
            ->limit(10)
            ->get();
    }

    private function getInactiveResources($query){
        // Clone the query to avoid modifying the original
        $resourceQuery = clone $query;
        
        // Get date range
        $dateRange = [
            $resourceQuery->getQuery()->wheres[0]['values'][0],
            $resourceQuery->getQuery()->wheres[0]['values'][1]
        ];
        
        // Get active resources
        $activeVehicleIds = $resourceQuery->pluck('loading_vehicle_id')
            ->merge($resourceQuery->pluck('unloading_vehicle_id'))
            ->filter()
            ->unique();
        
        $activeDriverIds = $resourceQuery->pluck('loading_driver_id')
            ->merge($resourceQuery->pluck('unloading_driver_id'))
            ->filter()
            ->unique();

        // Get inactive resources
        return [
            'inactive_vehicles' => Vehicle::whereNotIn('id', $activeVehicleIds)
                ->select('id', 'number', 'type')
                ->get(),
            'inactive_drivers' => User::whereNotIn('id', $activeDriverIds)
                ->whereHas('role', function($q) {
                    $q->where('name', 'driver');
                })
                ->select('id', 'name')
                ->get(),
        ];
    }

    private function getOverallStats($query){
        // Get date range
        $dateRange = [
            $query->getQuery()->wheres[0]['values'][0], 
            $query->getQuery()->wheres[0]['values'][1]
        ];

        // Get total counts
        $totalStats = [
            'total_users' => User::count(),
            'total_vehicles' => Vehicle::count(),
            'total_loading_clients' => Client::whereHas('loadingTransactions')->count(),
            'total_unloading_clients' => Client::whereHas('unloadingTransactions')->count(),
            'total_transactions' => Transaction::count(),
        ];

        // Get increments for the selected period
        $incrementStats = [
            'new_users' => User::whereBetween('created_at', $dateRange)->count(),
            'new_vehicles' => Vehicle::whereBetween('created_at', $dateRange)->count(),
            'new_loading_clients' => Client::whereBetween('created_at', $dateRange)
                ->whereHas('loadingTransactions')->count(),
            'new_unloading_clients' => Client::whereBetween('created_at', $dateRange)
                ->whereHas('unloadingTransactions')->count(),
            'new_transactions' => Transaction::whereBetween('loading_date', $dateRange)->count(),
        ];
    
        // Calculate growth percentages
        $growthStats = [
            'users_growth' => $totalStats['total_users'] > 0 
                ? round(($incrementStats['new_users'] / $totalStats['total_users']) * 100, 2) 
                : 0,
            'vehicles_growth' => $totalStats['total_vehicles'] > 0 
                ? round(($incrementStats['new_vehicles'] / $totalStats['total_vehicles']) * 100, 2) 
                : 0,
            'loading_clients_growth' => $totalStats['total_loading_clients'] > 0 
                ? round(($incrementStats['new_loading_clients'] / $totalStats['total_loading_clients']) * 100, 2) 
                : 0,
            'unloading_clients_growth' => $totalStats['total_unloading_clients'] > 0 
                ? round(($incrementStats['new_unloading_clients'] / $totalStats['total_unloading_clients']) * 100, 2) 
                : 0,
            'transactions_growth' => $totalStats['total_transactions'] > 0 
                ? round(($incrementStats['new_transactions'] / $totalStats['total_transactions']) * 100, 2) 
                : 0,
        ];

        return [
            'totals' => $totalStats,
            'increments' => $incrementStats,
            'growth_percentages' => $growthStats
        ];
    }
}
