<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getAnalytics(Request $request){
        return $this->tryCatchWrapper(function() use ($request) {
            if(Auth::user()->role->type !== 'admin') throw new \Exception('Unauthorized Request', 403);
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            $query = Transaction::query()
                ->whereBetween('loading_date', [$request->from_date, $request->to_date]);

            return [
                'message' => 'Dashboard analytics fetched successfully',
                'data' => [
                    // New overall statistics
                    'overall_stats' => $this->getOverallStats($query),
                    
                    // Existing analytics
                    'top_vehicles' => $this->getTopVehicles($query),
                    'top_drivers' => $this->getTopDrivers($query),
                    'products_by_quantity' => $this->getProductsByQuantity($query),
                    'loading_points_by_quantity' => $this->getLoadingPointsByQuantity($query),
                    'unloading_points_by_quantity' => $this->getUnloadingPointsByQuantity($query),
                    'loading_points_by_price' => $this->getLoadingPointsByPrice($query),
                    'unloading_points_by_price' => $this->getUnloadingPointsByPrice($query),
                    
                    // Other analytics
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
        return $query->select(
                'loading_vehicle_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('loadingVehicle:id,number,type')
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

    private function getTopDrivers($query)
    {
        return $query->select(
                'loading_driver_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(transport_expense) as total_expense'),
                DB::raw('AVG(transport_expense) as avg_expense')
            )
            ->with('loadingDriver:id,name')
            ->whereNotNull('loading_driver_id')
            ->groupBy('loading_driver_id')
            ->orderByDesc(DB::raw('AVG(transport_expense)'))
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

    private function getProductsByQuantity($query){
        return $query->select(
                'product_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_quantity) as total_loading_quantity'),
                DB::raw('SUM(unloading_quantity) as total_unloading_quantity')
            )
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByRaw('GREATEST(SUM(loading_quantity), SUM(unloading_quantity)) DESC')
            ->get()
            ->map(function($item) {
                return [
                    'product' => $item->product,
                    'transaction_count' => $item->transaction_count,
                    'total_loading_quantity' => $item->total_loading_quantity,
                    'total_unloading_quantity' => $item->total_unloading_quantity
                ];
            });
    }

    private function getLoadingPointsByQuantity($query){
        return $query->select(
                'loading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_quantity) as total_quantity')
            )
            ->with('loadingPoint:id,name')
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
        return $query->select(
                'unloading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(unloading_quantity) as total_quantity')
            )
            ->with('unloadingPoint:id,name')
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
        return $query->select(
                'loading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(loading_rate * loading_quantity) as total_price')
            )
            ->with('loadingPoint:id,name')
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
        return $query->select(
                'unloading_point_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(unloading_rate * unloading_quantity) as total_price')
            )
            ->with('unloadingPoint:id,name')
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
        return [
            'total_transactions' => $query->count(),
            'total_transport_expense' => $query->sum('transport_expense'),
            'average_transport_expense' => $query->avg('transport_expense'),
            'total_loading_quantity' => $query->sum('loading_quantity'),
            'total_unloading_quantity' => $query->sum('unloading_quantity'),
            'total_loading_value' => $query->selectRaw('SUM(loading_rate * loading_quantity) as total')->value('total'),
            'total_unloading_value' => $query->selectRaw('SUM(unloading_rate * unloading_quantity) as total')->value('total'),
        ];
    }

    private function getTransactionTypeStats($query){
        return $query->select('txn_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(transport_expense) as total_expense')
            ->selectRaw('AVG(transport_expense) as avg_expense')
            ->groupBy('txn_type')
            ->get();
    }

    private function getDailyTransactionStats($query){
        return $query->select(
                DB::raw('DATE(loading_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(transport_expense) as total_expense'),
                DB::raw('SUM(loading_quantity) as total_loading'),
                DB::raw('SUM(unloading_quantity) as total_unloading')
            )
            ->groupBy(DB::raw('DATE(loading_date)'))
            ->orderBy('date')
            ->get();
    }

    private function getQuantityDiscrepancies($query){
        return $query->select(
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
        $activeVehicleIds = $query->pluck('loading_vehicle_id')->merge(
            $query->pluck('unloading_vehicle_id')
        )->unique();
        
        $activeDriverIds = $query->pluck('loading_driver_id')->merge(
            $query->pluck('unloading_driver_id')
        )->unique();

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
            'new_users' => User::whereBetween('created_at', [$query->getQuery()->wheres[0]['values'][0], $query->getQuery()->wheres[0]['values'][1]])->count(),
            'new_vehicles' => Vehicle::whereBetween('created_at', [$query->getQuery()->wheres[0]['values'][0], $query->getQuery()->wheres[0]['values'][1]])->count(),
            'new_loading_clients' => Client::whereHas('loadingTransactions', function($q) use ($query) {
                $q->whereBetween('loading_date', [$query->getQuery()->wheres[0]['values'][0], $query->getQuery()->wheres[0]['values'][1]]);
            })->where('created_at', '>=', $query->getQuery()->wheres[0]['values'][0])->count(),
            'new_unloading_clients' => Client::whereHas('unloadingTransactions', function($q) use ($query) {
                $q->whereBetween('loading_date', [$query->getQuery()->wheres[0]['values'][0], $query->getQuery()->wheres[0]['values'][1]]);
            })->where('created_at', '>=', $query->getQuery()->wheres[0]['values'][0])->count(),
            'new_transactions' => $query->count(),
        ];

        // Calculate growth percentages
        $growthStats = [
            'users_growth' => $totalStats['total_users'] > 0 ? 
                round(($incrementStats['new_users'] / $totalStats['total_users']) * 100, 2) : 0,
            'vehicles_growth' => $totalStats['total_vehicles'] > 0 ? 
                round(($incrementStats['new_vehicles'] / $totalStats['total_vehicles']) * 100, 2) : 0,
            'loading_clients_growth' => $totalStats['total_loading_clients'] > 0 ? 
                round(($incrementStats['new_loading_clients'] / $totalStats['total_loading_clients']) * 100, 2) : 0,
            'unloading_clients_growth' => $totalStats['total_unloading_clients'] > 0 ? 
                round(($incrementStats['new_unloading_clients'] / $totalStats['total_unloading_clients']) * 100, 2) : 0,
            'transactions_growth' => $totalStats['total_transactions'] > 0 ? 
                round(($incrementStats['new_transactions'] / $totalStats['total_transactions']) * 100, 2) : 0,
        ];

        return [
            'totals' => $totalStats,
            'increments' => $incrementStats,
            'growth_percentages' => $growthStats
        ];
    }
}
