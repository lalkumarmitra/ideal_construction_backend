<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\TransactionExportRequest;
use App\Http\Requests\Transaction\TransactionSearchFilterRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Services\TransactionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionController extends Controller
{
    public function store(CreateTransactionRequest $request) {
        return $this->tryCatchWrapper(function()use($request){
            $product = Product::find($request->product_id);
            $loading_vehicle = Vehicle::find($request->loading_vehicle_id);
            $unloading_vehicle = Vehicle::find($request->unloading_vehicle_id);
            $loading_point = Client::find($request->loading_point_id);
            $unloading_point = Client::find($request->unloading_point_id);

            DB::beginTransaction();
            try{
                $transaction = Transaction::create([
                    "product_id"=>$product->id,
                    "loading_point_id"=>$loading_point->id,
                    "loading_vehicle_id"=>$loading_vehicle->id,
                    "loading_date"=>$request->loading_date ?? date('Y-m-d'),
                    "loading_rate"=>$request->loading_rate ?? 0,
                    "loading_quantity"=>$request->loading_quantity ?? 0,
                    "unloading_point_id"=>$unloading_point->id,
                    "unloading_vehicle_id"=>$unloading_vehicle->id ?? null,
                    "unloading_date"=>$request->unloading_date,
                    "unloading_rate"=>$request->unloading_rate,
                    "unloading_quantity"=>$request->unloading_quantity,
                    "do_number"=>$request->do_number,
                    "challan_number"=>$request->challan_number,
                    "txn_type"=>$request->txn_type ?? 'normal',
                    // newly added columns
                    "transport_expense"=>$request->transport_expense ?? 0,
                    "loading_driver_id"=>$request->loading_driver_id,
                    "unloading_driver_id"=>$request->unloading_driver_id,
                    "recorder_id" => Auth::user()->id,
                    "updater_id" => Auth::user()->id,
                ]);
                if($product) $product->increment('frequency_of_use');
                if($loading_point) $loading_point->increment('frequency_of_use');
                if($unloading_point) $unloading_point->increment('frequency_of_use');
                if($loading_vehicle) $loading_vehicle->increment('frequency_of_use');
                if($unloading_vehicle) $unloading_vehicle->increment('frequency_of_use');
                DB::commit();
                return [
                    'message'=>'Transaction Recorded Successfully',
                    'data'=>['transaction'=>$transaction->load([
                        "product",
                        "loadingPoint",
                        "unloadingPoint",
                        "loadingVehicle",
                        "unloadingVehicle",
                    ])]
                ];
            }catch(Exception $e){
                DB::rollBack();
                throw new Exception('Could not Create transaction, '.$e->getMessage());
            }
        });
    }
    public function update(CreateTransactionRequest $request, $id) {
        return $this->tryCatchWrapper(function () use ($request, $id) {
            $transaction = Transaction::findOrFail($id);
            
            DB::transaction(function () use ($transaction, $request) {
                $product = Product::firstWhere('id', $request->product_id);
                $loading_vehicle = Vehicle::firstWhere('id', $request->loading_vehicle_id);
                $unloading_vehicle = Vehicle::firstWhere('id', $request->unloading_vehicle_id);
                $loading_point = Client::firstWhere('id', $request->loading_point_id);
                $unloading_point = Client::firstWhere('id', $request->unloading_point_id);
    
                // Increment frequency only if the related item changed
                if ($product && $product->id !== $transaction->product_id) $product->increment('frequency_of_use');
                if ($loading_vehicle && $loading_vehicle->id !== $transaction->loading_vehicle_id) $loading_vehicle->increment('frequency_of_use');
                if ($unloading_vehicle && $unloading_vehicle->id !== $transaction->unloading_vehicle_id) $unloading_vehicle->increment('frequency_of_use');
                if ($loading_point && $loading_point->id !== $transaction->loading_point_id) $loading_point->increment('frequency_of_use');
                if ($unloading_point && $unloading_point->id !== $transaction->unloading_point_id) $unloading_point->increment('frequency_of_use');
    
                $transaction->update([
                    "product_id" => $product?->id ?? $transaction->product_id,
                    "loading_point_id" => $loading_point?->id ?? $transaction->loading_point_id,
                    "loading_vehicle_id" => $loading_vehicle?->id ?? $transaction->loading_vehicle_id,
                    "loading_date" => $request->loading_date ?? $transaction->loading_date,
                    "loading_rate" => $request->loading_rate ?? $transaction->loading_rate,
                    "loading_quantity" => $request->loading_quantity ?? $transaction->loading_quantity,
                    "unloading_point_id" => $unloading_point?->id ?? $transaction->unloading_point_id,
                    "unloading_vehicle_id" => $unloading_vehicle?->id ?? $transaction->unloading_vehicle_id,
                    "unloading_date" => $request->unloading_date ?? $transaction->unloading_date,
                    "unloading_rate" => $request->unloading_rate ?? $transaction->unloading_rate,
                    "unloading_quantity" => $request->unloading_quantity ?? $transaction->unloading_quantity,
                    "do_number" => $request->do_number ?? $transaction->do_number,
                    "challan_number" => $request->challan_number ?? $transaction->challan_number,
                    "txn_type" => $request->txn_type ?? $transaction->txn_type,
                    "transport_expense" => $request->transport_expense ?? $transaction->transport_expense,
                    "loading_driver_id" => $request->loading_driver_id ?? $transaction->loading_driver_id,
                    "unloading_driver_id" => $request->unloading_driver_id ?? $transaction->unloading_driver_id,
                    "updater_id" => Auth::id(),
                ]);
            });
    
            return [
                'message' => 'Transaction Record Updated Successfully',
                'data' => [
                    'transaction' => $transaction->load([
                        "product",
                        "loadingPoint",
                        "unloadingPoint",
                        "loadingVehicle",
                        "unloadingVehicle",
                    ]),
                ],
            ];
        });
    }
    
    public function read($page=1,$offset=10){
        return $this->tryCatchWrapper(function()use($page,$offset){
            $transactions = Transaction::latest()->with([
                "product",
                "loadingPoint",
                "unloadingPoint",
                "loadingVehicle",
                "unloadingVehicle",
                "loadingDriver",
                "unLoadingDriver",
            ])->paginate($offset, ['*'], 'page', $page);
            return [
                'message'=>'Transactions Fetched Successfully',
                'data'=>[
                    'transactions' => $transactions->items(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ]
            ];
        });
    }

    public function search(TransactionExportRequest $request,$page=1,$offset=20) {
        return $this->tryCatchWrapper(function()use($request,$page,$offset){
            $query = Transaction::with([ 'product', 'loadingPoint', 'unloadingPoint', 'loadingVehicle', 'unloadingVehicle','loadingDriver','unLoadingDriver' ]);
            if ($loadingClientSize = $request->input('loading_client_size')) $query->filterByClientSize($loadingClientSize,'loading');
            if ($unLoadingClientSize = $request->input('unloading_client_size')) $query->filterByClientSize($unLoadingClientSize,'unloading');

            if ($loadingClientSizes = $request->input('loading_client_sizes')) $query->filterByClientSize($loadingClientSizes,'loading');
            if ($unLoadingClientSizes = $request->input('unloading_client_sizes')) $query->filterByClientSize($unLoadingClientSizes,'unloading');
            if ($request->filled('loading_date_from') || $request->filled('loading_date_to')) {
                $query->filterByDateRange(
                    'loading',
                    $request->input('loading_date_from'),
                    $request->input('loading_date_to')
                );
            }
            if ($request->filled('unloading_date_from') || $request->filled('unloading_date_to')) {
                $query->filterByDateRange(
                    'unloading',
                    $request->input('unloading_date_from'),
                    $request->input('unloading_date_to')
                );
            }
            (new TransactionService($request, $query))->applyFilters();
            if($request->filled('sort_field') && $request->filled('sort_order')) $query->orderBy($request->input('sort_field'), $request->input('sort_order'));
            else $query->latest();
            $transactions = $query->paginate($offset, ['*'], 'page', $page);
            return [
                'message' => 'Search results',
                'data'=>[
                    'transactions' => $transactions->items(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ]
            ];
        });
    }
    public function export(TransactionExportRequest $request) {
        return $this->tryCatchWrapper(function()use($request){
            $query = Transaction::with(['product', 'loadingPoint', 'unloadingPoint', 'loadingVehicle', 'unloadingVehicle','loadingDriver','unLoadingDriver']);
            if ($loadingClientSize = $request->input('loading_client_size')) $query->filterByClientSize($loadingClientSize,'loading');
            if ($unLoadingClientSize = $request->input('unloading_client_size')) $query->filterByClientSize($unLoadingClientSize,'unloading');

            if ($loadingClientSizes = $request->input('loading_client_sizes')) $query->filterByClientSize($loadingClientSizes,'loading');
            if ($unLoadingClientSizes = $request->input('unloading_client_sizes')) $query->filterByClientSize($unLoadingClientSizes,'unloading');
            if ($request->filled('loading_date_from') || $request->filled('loading_date_to')) {
                $query->filterByDateRange(
                    'loading',
                    $request->input('loading_date_from'),
                    $request->input('loading_date_to')
                );
            }
            if ($request->filled('unloading_date_from') || $request->filled('unloading_date_to')) {
                $query->filterByDateRange(
                    'unloading',
                    $request->input('unloading_date_from'),
                    $request->input('unloading_date_to')
                );
            }
            if($request->filled('sort_field') && $request->filled('sort_order')) $query->orderBy($request->input('sort_field'), $request->input('sort_order'));
            else $query->latest();
            $excelData = (new TransactionService($request, $query))->export();
            return [
                'message' => 'Excel file generated successfully',
                'type' => 'download',
                'data'=>$excelData
            ];
        });
    }
    public function details($id){
        return $this->tryCatchWrapper(function()use($id){
            if(!$transaction = Transaction::find($id)) throw new Exception('Transaction not found with ID : '.$id,404);
            return [
                'message'=>'Transaction details Fetched Successfully',
                'data'=>['transaction' => $transaction]
            ];
        });
    }
    public function delete($id){
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$transaction = Transaction::find($id)) throw new Exception('Transaction not found with ID : '.$id,404);
            $transaction->delete();
            return [
                'message'=>'Transaction deleted Successfully',
                'data'=>['tr$transaction' => $transaction]
            ];
        });
    }
}
