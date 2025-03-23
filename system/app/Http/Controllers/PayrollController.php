<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\DownloadPayrollRequest;
use App\Models\User;
use App\Services\PayrollService;
use Exception;

class PayrollController extends Controller
{
    public function downloadPayroll(DownloadPayrollRequest $request, $userId) {
        return $this->tryCatchWrapper(function() use ($request, $userId) {
            if (empty($user = User::find($userId))) throw new Exception('Invalid user id', 404);
            
            $payrollService = new PayrollService($user);
    
            if ($request->filled('month')) {
                $payrollService->setMonth((int)$request->month, $request->year);
            } elseif ($request->filled('start_date') && $request->filled('end_date')) {
                $payrollService->setDates($request->start_date, $request->end_date);
            }
    
            $payroll = $payrollService->generatePayroll();
            return [
                'message' => 'Payroll generated successfully',
                'type' => 'download',
                'file_type' == 'pdf',
                'data' => $payroll
            ];
        });
    }
    
}
