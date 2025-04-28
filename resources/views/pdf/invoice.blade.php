<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .total { font-weight: bold; }
        .status-paid { color: green; }
        .status-pending { color: orange; }
        .status-failed { color: red; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    <p><strong>Customer:</strong> {{ $customer->name }} ({{ $customer->email }})</p>
    <p><strong>Order ID:</strong> {{ $order->id }}</p>
    <p><strong>Date:</strong> {{ $invoice->created_at->format('Y-m-d') }}</p>

    <h2>Order Details:</h2>
    <table>
        <tr>
            <th>Description</th>
            <th>Amount</th>
            <th>Currency</th>
        </tr>
        <tr>
            <td>{{ $invoice->order_description ?? 'Order Payment' }}</td>
            <td>${{ number_format($invoice->price_amount, 2) }}</td>
            <td>{{ strtoupper($invoice->price_currency) }}</td>
        </tr>
    </table>

    <h3 class="total">
        Total Due: ${{ number_format($invoice->price_amount, 2) }} {{ strtoupper($invoice->price_currency) }}
    </h3>

    <p><strong>Payment Status:</strong> 
        <span class="status-{{ strtolower($invoice->payment_status ?? 'pending') }}">
            {{ ucfirst($invoice->payment_status ?? 'Pending') }}
        </span>
    </p>

    <p>Thank you for your trust!</p>
</body>
</html>
