<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Invoice - Ideal Construction</title>
    <style>
        /* Base styles optimized for PDF generation */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            line-height: 1.3;
            font-size: 11pt;
        }
        
        /* Watermark - adjusted for better PDF rendering */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            width: 60%;
            height: auto;
        }
        
        /* Header styles - reduced sizes for PDF */
        .header {
            text-align: center;
            border-bottom: 2px solid #2c5282;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .text-right {
            text-align: right;
        }
        .uppercase {
            text-transform: 'uppercase';
        }
        .logo-container {
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2c5282;
            margin: 5px 0;
        }
        
        .document-title {
            font-size: 14pt;
            font-weight: bold;
            background-color: #edf2f7;
            padding: 5px;
            margin: 10px auto;
            width: 40%;
            text-align: center;
            border-radius: 3px;
        }
        
        .invoice-details {
            margin: 10px 0;
            font-size: 10pt;
        }
        
        /* Information sections - simplified for PDF */
        .info-section {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .section-header {
            background-color: #2c5282;
            color: white;
            font-weight: bold;
            padding: 5px 10px;
            font-size: 11pt;
        }
        
        .section-content {
            padding: 8px 10px;
            background-color: #f8fafc;
        }
        
        /* Driver info and period styles */
        .info-row {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 15px;
        }
        
        .info-column {
            display: table-cell;
            width: 49%;
        }
        
        .info-column:first-child {
            padding-right: 10px;
        }
        
        .label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        /* Smaller spacing for info blocks */
        .section-content p {
            margin: 5px 0;
        }
        
        /* Table styles - optimized for PDF */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        
        th {
            
            text-align: left;
            padding: 5px 8px;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
        }
        
        td {
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .total-row {
            background-color: #2c5282;
            font-weight: bold;
        }
        td {
            white-space: nowrap;
            font-size: 10px;
        }
        
        /* Payment details */
        .signature-section {
            margin-top: 5px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
            margin-left: 10px;
        }
        
        .payment-field {
            margin-bottom: 10px;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9pt;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            position: fixed;
            bottom: 20px;
            left: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Watermark - Using base64 for reliable embedding -->
    <div class="watermark">
        <img src="{{ public_path('assets/logo.png') }}" alt="">
    </div>
    
    <!-- Header -->
    <div class="header">
        <div class="logo-container">
            <img src="{{ public_path('assets/logo.png') }}" alt="Ideal Construction Logo" width="100">
        </div>
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
                    <p><span class="label">Transactions:</span> {{ $payrollData['total_transactions'] }} Transactions</p>
                </div>
            </div>
        </div>
        <div class="info-column">
            <div class="info-section">
                <div class="section-header">Payroll Period</div>
                <div class="section-content">
                    <p style="margin-bottom: 4px" class="label">{{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
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
        </div>
    </div>
    
    <!-- Transaction Details -->
    <div class="info-section">
        <div class="section-header">Transaction Details</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="20%">Product</th>
                        <th width="30%">Route</th>
                        <th class="text-right" width="15%">Quantity</th>
                        <th class="text-right" width="20%">Expense</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrollData['transactions'] as $transaction)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($transaction['loading_date'])->format('d M Y') }}</td>
                        <td>{{ $transaction['product']['name'] ?? 'N/A' }}</td>
                        <td>{{ $transaction['loadingPoint']['name'] ?? 'N/A' }}  -  {{ $transaction['unloadingPoint']['name'] ?? 'N/A' }}</td>
                        <td class="text-right uppercase">{{ formatIndianNumber(number_format($transaction['unloading_quantity'], 3)) }} {{ strtoupper($transaction['unit']) }}</td>
                        <td class="text-right">{{ formatIndianNumber(number_format($transaction['transport_expense'], 2)) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td>#{{ $payrollData['total_transactions'] }} transactions</td>
                        <td class="text-right">{{ formatIndianNumber(number_format($payrollData['total_unloaded_quantity'], 3)) }} MT</td>
                        <td class="text-right">RS {{ formatIndianNumber(number_format($payrollData['total_expense'], 2)) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    
    <!-- Footer -->
    <div class="footer">
        <p>This is an official payment document of Ideal Construction</p>
        <p>If you have any questions, please contact +91-7858856423</p>
    </div>
</body>
</html>