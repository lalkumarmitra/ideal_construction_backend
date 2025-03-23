<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payroll Invoice - Ideal Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            position: relative;
            padding: 2rem;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            width: 70%;
            height: auto;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 250px;
            margin-left: 10px;
        }
    </style>
</head>
<body class="bg-white text-gray-800">
    <!-- Watermark -->
    <img src="https://api.idealconstruction.online/assets/logo.png" alt="" class="watermark">
    
    <!-- Header Section -->
    <div class="border-b-4 border-blue-500 pb-6 flex justify-between items-center">
        <div>
            <img src="https://api.idealconstruction.online/assets/logo.png" alt="Ideal Construction Logo" class="h-20 mb-2">
            <h1 class="text-3xl font-bold text-blue-700">Ideal Construction</h1>
        </div>
        <div class="text-right">
            <h2 class="text-2xl font-bold text-blue-600 bg-gray-100 px-4 py-2 rounded-lg">PAYROLL INVOICE</h2>
            <p class="text-gray-600 mt-2">Invoice #: {{ date('Ymd') }}-{{ $user->id ?? 'XXX' }}</p>
            <p class="text-gray-600">Date: {{ date('d M Y') }}</p>
        </div>
    </div>
    
    <!-- Driver and Period Info -->
    <div class="mt-8 flex flex-wrap">
        <div class="w-full md:w-1/2 p-4 bg-gray-50 rounded-lg shadow-sm">
            <h3 class="font-bold text-lg text-blue-600 border-b border-blue-200 pb-2 mb-3">Driver Details</h3>
            <div class="ml-2">
                <p class="mb-2"><span class="font-semibold">Name:</span> {{ $user->name ?? 'N/A' }}</p>
                <p class="mb-2"><span class="font-semibold">Phone:</span> {{ $user->phone ?? 'N/A' }}</p>
                <p class="mb-2"><span class="font-semibold">Email:</span> {{ $user->email ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="w-full md:w-1/2 p-4">
            <h3 class="font-bold text-lg text-blue-600 border-b border-blue-200 pb-2 mb-3">Payroll Period</h3>
            <p class="text-lg bg-blue-50 p-2 rounded border-l-4 border-blue-400">
                {{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>
    </div>
    
    <!-- Transaction Details -->
    <div class="mt-8">
        <h3 class="font-bold text-xl text-blue-700 mb-4 bg-gray-100 p-2 rounded">Transaction Details</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gradient-to-r from-blue-600 to-blue-400 text-white">
                        <th class="py-3 px-4 text-left rounded-tl-lg">Date</th>
                        <th class="py-3 px-4 text-left">Product</th>
                        <th class="py-3 px-4 text-left">Route</th>
                        <th class="py-3 px-4 text-left">Quantity</th>
                        <th class="py-3 px-4 text-left rounded-tr-lg">Expense</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrollData['transactions'] as $index => $transaction)
                    <tr class="{{ $index % 2 == 0 ? 'bg-blue-50' : 'bg-white' }} hover:bg-blue-100 transition-colors">
                        <td class="py-3 px-4 border-b border-gray-200">{{ Carbon\Carbon::parse($transaction['loading_date'])->format('d M Y') }}</td>
                        <td class="py-3 px-4 border-b border-gray-200">{{ $transaction['product']['name'] ?? 'N/A' }}</td>
                        <td class="py-3 px-4 border-b border-gray-200">
                            <span class="text-blue-700">{{ $transaction['loadingPoint']['name'] ?? 'N/A' }}</span>
                            <span class="mx-2">→</span>
                            <span class="text-green-700">{{ $transaction['unloadingPoint']['name'] ?? 'N/A' }}</span>
                        </td>
                        <td class="py-3 px-4 border-b border-gray-200">{{ number_format($transaction['unloading_quantity'], 3) }} {{ $transaction['unit'] }}</td>
                        <td class="py-3 px-4 border-b border-gray-200 font-medium">RS {{ number_format($transaction['transport_expense'], 3) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-blue-700 text-white font-bold">
                        <td class="py-3 px-4 rounded-bl-lg" colspan="2">Total</td>
                        <td class="py-3 px-4">#{{ $payrollData['total_transactions'] }} transactions</td>
                        <td class="py-3 px-4">{{ number_format($payrollData['total_unloaded_quantity'], 3) }} MT</td>
                        <td class="py-3 px-4 rounded-br-lg">RS {{ number_format($payrollData['total_expense'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- Payment Section -->
    <div class="mt-10 p-6 border-2 border-dashed border-blue-300 rounded-lg bg-blue-50">
        <h3 class="font-bold text-xl text-blue-700 mb-4">Payment Details</h3>
        <div class="flex items-center mb-4">
            <span class="font-semibold w-32">Amount:</span>
            <span class="signature-line"></span>
        </div>
        <div class="flex items-center">
            <span class="font-semibold w-32">Signature:</span>
            <span class="signature-line"></span>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-12 text-center text-sm text-gray-500 border-t pt-4">
        <p>This is an official payment document of Ideal Construction</p>
        <p>If you have any questions, please contact accounting@idealconstruction.com</p>
    </div>
</body>
</html>