<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { margin-bottom: 30px; }
        .summary { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Payroll Report</h2>
        <p>Period: {{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        
        <h3>Driver Details</h3>
        <p>Name: {{ $user->name ?? 'N/A'}}</p>
        <p>Phone: {{ $user->phone ?? 'N/A'}}</p>
        <p>Email: {{ $user->email ?? 'N/A'}}</p>
    </div>

    <div class="summary">
        <h3>Summary</h3>
        <p>Total Transactions: {{ $payrollData['total_transactions'] }}</p>
        <p>Total Expense: RS {{ number_format($payrollData['total_expense'], 2) }}</p>
        <p>Total Quantity: {{ number_format($payrollData['total_unloaded_quantity'], 2) }} MT</p>
        <p>Total Value: RS {{ number_format($payrollData['total_price'], 2) }}</p>
    </div>

    <h3>Transaction Details</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Route</th>
                <th>Quantity</th>
                <th>Expense</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payrollData['transactions'] as $transaction)
            <tr>
                <td>{{ Carbon\Carbon::parse($transaction->loading_date)->format('d M Y') }}</td>
                <td>{{ $transaction->product->name ?? 'N/A' }}</td>
                <td>{{ $transaction->loadingPoint->name  ?? 'N/A'}} → {{ $transaction->unloadingPoint->name ?? 'N/A' }}</td>
                <td>{{ number_format($transaction->unloading_quantity, 2) }} {{ $transaction->unit }}</td>
                <td>RS {{ number_format($transaction->transport_expense, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>