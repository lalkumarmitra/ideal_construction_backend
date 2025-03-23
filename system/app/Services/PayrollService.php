<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PayrollService {

    private $user = null;
    private $startDate = null;
    private $endDate = null;

    public function __construct(User $user, $startDate=null, $endDate=null){
        $this->user = $user;
        $this->startDate = Carbon::parse($startDate)->format('Y-m-d');
        $this->endDate = Carbon::parse($endDate)->format('Y-m-d');
    }
    // date funcitons for function chaining
    public function setDates($startDate,$endDate) {
        $this->startDate = Carbon::parse($startDate)->format('Y-m-d');
        $this->endDate = Carbon::parse($endDate)->format('Y-m-d');
        return $this;
    }
    public function setMonth($month,$year=null){
        $year = $year ?? Carbon::now()->year;
        if (!is_int($month) || $month < 1 || $month > 12) {
            throw new InvalidArgumentException("Month should be an integer between 1 and 12.");
        }
        if ($year !== null && (!is_int($year) || strlen((string)$year) !== 4)) {
            throw new InvalidArgumentException("Year should be a four-digit number.");
        }
        $this->startDate = Carbon::create($year, $month, 1)->format('Y-m-d');
        $this->endDate = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        return $this;
    }

    // payroll data functions
    

    public function generatePayroll() {
        try {
            $payrollData = $this->getPayrollData();
            if (!$payrollData || empty($payrollData['transactions'])) {
                throw new InvalidArgumentException("No payroll data available for the selected period");
            }
    
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.payroll', [
                'user' => $this->user,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'payrollData' => $payrollData
            ]);
    
            $filename = 'payroll_' . $this->user->id . '_' . Carbon::parse($this->startDate)->format('Y_m') . '.pdf';
            $exportDir = 'public/exports';
            Storage::makeDirectory($exportDir);
            $tempPath = Storage::path($exportDir . '/' . $filename);
            $pdf->save($tempPath);
    
            return [
                'path' => $tempPath,
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            throw new Exception('Error generating payroll: ' . $e->getMessage());
        }
    }


    public function getPayrollData(){
        if($this->user->role->type === 'driver'){
            return $this->getDriverPayrollData();
        }
        return null;
    }


    // private functions
    private function getDriverPayrollData() {
        $user = $this->user;
        $startDate = $this->startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $baseQuery = Transaction::where(function($query) use($user) {
            $query->where('loading_driver_id', $user->id)->orWhere('unloading_driver_id', $user->id);
        });
        $baseQuery->whereBetween('loading_date', [$startDate, $endDate]);
        $baseQuery->with([
            'product:id,name',
            'loadingPoint:id,name',
            'unloadingPoint:id,name'
        ]);
        $totalTransactions = $baseQuery->count();
        $totalExpense = $baseQuery->sum('transport_expense');
        $totalUnloadedQuantity = $baseQuery->sum('unloading_quantity');
        $totalPrice = $baseQuery->selectRaw('SUM(unloading_quantity * unloading_rate) as total_price')->value('total_price') ?? 0;
        $transactions = $baseQuery->get();
        return [
            'total_transactions' => $totalTransactions,
            'total_expense' => $totalExpense,
            'total_unloaded_quantity' => $totalUnloadedQuantity,
            'total_price' => $totalPrice,
            'transactions' => $transactions->map(function($transaction) {
                return [
                    'loading_date' => $transaction->loading_date,
                    'product' => [
                        'name' => $transaction->product->name ?? 'N/A'
                    ],
                    'loadingPoint' => [
                        'name' => $transaction->loadingPoint->name ?? 'N/A'
                    ],
                    'unloadingPoint' => [
                        'name' => $transaction->unloadingPoint->name ?? 'N/A'
                    ],
                    'unloading_quantity' => $transaction->unloading_quantity,
                    'unit' => $transaction->unit ?? '',
                    'transport_expense' => $transaction->transport_expense
                ];
            })
        ];
    }
}