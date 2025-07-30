<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 15px;
            margin: 0;
            background: #f7fafc;
            color: #222;
        }
        .invoice-container {
            max-width: 600px;
            margin: 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px 0 rgba(16, 185, 129, 0.10);
            padding: 32px 24px 24px 24px;
        }
        header {
            border-bottom: 2px solid #10b981;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        h1 {
            color: #10b981;
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 8px 0;
            letter-spacing: -1px;
        }
        .meta {
            font-size: 1rem;
            color: #374151;
            margin-bottom: 8px;
        }
        .section-title {
            color: #047857;
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 24px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #f9fafb;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background: #10b981;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }
        .total {
            font-weight: 800;
            font-size: 1.2rem;
            color: #047857;
            margin-top: 18px;
        }
        .status {
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 1rem;
            display: inline-block;
        }
        .status-paid { background: #d1fae5; color: #047857; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-failed { background: #fee2e2; color: #b91c1c; }
        @media (max-width: 600px) {
            .invoice-container { padding: 16px 4vw; }
            h1 { font-size: 1.4rem; }
            th, td { font-size: 0.95rem; padding: 7px 6px; }
            .total { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <main class="invoice-container">
        <header>
            <h1>Invoice #{{ $invoice->id }}</h1>
            <div class="meta"><strong>Customer:</strong> {{ $customer->name }} ({{ $customer->email }})</div>
            <div class="meta"><strong>Order ID:</strong> {{ $order->id }}</div>
            <div class="meta"><strong>Date:</strong> {{ $invoice->created_at->format('Y-m-d') }}</div>
        </header>

        <section>
            <div class="section-title">Order Details</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $invoice->order_description ?? 'Order Payment' }}</td>
                        <td>${{ number_format($invoice->price_amount, 2) }}</td>
                        <td>{{ strtoupper($invoice->price_currency) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <div class="total">
            Total Due: ${{ number_format($invoice->price_amount, 2) }} {{ strtoupper($invoice->price_currency) }}
        </div>

        <div style="margin-top: 18px;">
            <strong>Payment Status:</strong>
            <span class="status status-{{ strtolower($invoice->payment_status ?? 'pending') }}">
                {{ ucfirst($invoice->payment_status ?? 'Pending') }}
            </span>
        </div>

        <footer style="margin-top: 32px; text-align: center; color: #6b7280; font-size: 0.98rem;">
            Thank you for your trust!
        </footer>
    </main>
</body>
</html>
