<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Invoice - Ideal Construction</title>
    <style>
        /* Base styles that work well with PDF generators */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
            position: relative;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            width: 70%;
        }
        
        /* Header styles */
        .header {
            text-align: center;
            border-bottom: 3px solid #2c5282;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c5282;
            margin: 10px 0;
        }
        
        .document-title {
            font-size: 22px;
            font-weight: bold;
            background-color: #edf2f7;
            padding: 8px;
            margin: 15px auto;
            width: 50%;
            text-align: center;
            border-radius: 5px;
        }
        
        .invoice-details {
            margin: 15px 0;
        }
        
        /* Information sections */
        .info-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            background-color: #2c5282;
            color: white;
            font-weight: bold;
            padding: 8px 15px;
            font-size: 16px;
        }
        
        .section-content {
            padding: 15px;
            background-color: #f8fafc;
        }
        
        /* Driver info and period styles */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        
        .info-column {
            width: 48%;
        }
        
        .label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background-color: #2c5282;
            color: white;
            text-align: left;
            padding: 10px;
            font-weight: bold;
        }
        
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .total-row {
            background-color: #2c5282;
            color: white;
            font-weight: bold;
        }
        
        /* Payment details */
        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 250px;
            margin-left: 10px;
        }
        
        .payment-field {
            margin-bottom: 15px;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">
        <img src="https://api.idealconstruction.online/assets/logo.png" alt="" width="100%">
    </div>
    
    <!-- Header -->
    <div class="header">
        <img src="https://api.idealconstruction.online/assets/logo.png" alt="Ideal Construction Logo" width="150">
        <div class="company-name">Ideal Construction</div>
        <div class="document-title">PAYROLL INVOICE</div>
        <div class="invoice-details">
            <div>Invoice #: {{ date('Ymd') }}-{{ $user->id ?? 'XXX' }}</div>
            <div>Date: {{ date('d M Y') }}</div>
        </div>
    </div>
    
    <!-- Driver and Period Info -->
    <div class="info-row">
        <div class="info-column">
            <div class="info-section">
                <div class="section-header">Driver Details</div>
                <div class="section-content">
                    <p><span class="label">Name:</span> {{ $user->name ?? 'N/A' }}</p>
                    <p><span class="label">Phone:</span> {{ $user->phone ?? 'N/A' }}</p>
                    <p><span class="label">Email:</span> {{ $user->email ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="info-column">
            <div class="info-section">
                <div class="section-header">Payroll Period</div>
                <div class="section-content">
                    <p>{{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Details -->
    <div class="info-section">
        <div class="section-header">Transaction Details</div>
        <div class="section-content">
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
                        <td>{{ Carbon\Carbon::parse($transaction['loading_date'])->format('d M Y') }}</td>
                        <td>{{ $transaction['product']['name'] ?? 'N/A' }}</td>
                        <td>{{ $transaction['loadingPoint']['name'] ?? 'N/A' }} → {{ $transaction['unloadingPoint']['name'] ?? 'N/A' }}</td>
                        <td>{{ number_format($transaction['unloading_quantity'], 3) }} {{ $transaction['unit'] }}</td>
                        <td>RS {{ number_format($transaction['transport_expense'], 3) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td>#{{ $payrollData['total_transactions'] }} transactions</td>
                        <td>{{ number_format($payrollData['total_unloaded_quantity'], 3) }} MT</td>
                        <td>RS {{ number_format($payrollData['total_expense'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Payment Details -->
    <div class="info-section">
        <div class="section-header">Payment Details</div>
        <div class="section-content">
            <div class="payment-field">
                <span class="label">Amount:</span>
                <span class="signature-line"></span>
            </div>
            <div class="payment-field">
                <span class="label">Signature:</span>
                <span class="signature-line"></span>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>This is an official payment document of Ideal Construction</p>
        <p>If you have any questions, please contact accounting@idealconstruction.com</p>
    </div>
</body>
</html>