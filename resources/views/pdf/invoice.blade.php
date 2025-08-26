<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', '1000PROXY') }} • Invoice #{{ $invoice->id }}</title>
    <style>
        /* Brand palette: emerald accents, neutral backgrounds. Avoid remote assets. */
        body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; margin: 0; background: #f7fafc; color: #111827; }
        .invoice-container { max-width: 720px; margin: 28px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(16,185,129,0.10); padding: 28px 24px; }
        header { border-bottom: 2px solid #10b981; padding-bottom: 16px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-badge { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #10b981, #34d399); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; letter-spacing: -0.5px; }
        h1 { color: #065f46; font-size: 1.6rem; font-weight: 800; margin: 0; letter-spacing: -0.5px; }
        .muted { color: #374151; }
        .meta { font-size: 0.95rem; color: #374151; margin-bottom: 6px; }
        .grid { display: table; width: 100%; table-layout: fixed; border-spacing: 0 10px; }
        .cell { display: table-cell; vertical-align: top; }
        .section-title { color: #047857; font-size: 1.05rem; font-weight: 700; margin: 18px 0 8px; letter-spacing: 0.3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; background: #f9fafb; border-radius: 8px; overflow: hidden; }
        th, td { border: 1px solid #e5e7eb; padding: 9px 10px; text-align: left; }
        th { background: #10b981; color: #fff; font-weight: 700; font-size: 0.95rem; }
        .total { font-weight: 800; font-size: 1.1rem; color: #065f46; margin-top: 14px; }
        .status { font-weight: 700; padding: 4px 10px; border-radius: 8px; font-size: 0.95rem; display: inline-block; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-failed, .status-cancelled { background: #fee2e2; color: #991b1b; }
        .k { color: #374151; font-weight: 600; }
        .code { font-family: "Courier New", Courier, monospace; background: #f3f4f6; border: 1px solid #e5e7eb; padding: 2px 6px; border-radius: 6px; word-break: break-all; }
        footer { margin-top: 24px; text-align: center; color: #6b7280; font-size: 0.92rem; }
        .hr { height: 1px; background: #e5e7eb; border: none; margin: 16px 0; }
        .small { font-size: 0.85rem; }
        @page { margin: 12mm 10mm; }
    </style>
    <!-- No remote images/fonts. All data is escaped by default. -->
    <?php
        // Lightweight integrity code to help support verify this document when needed.
        $documentIntegrity = substr(sha1(($invoice->id ?? '') . '|' . ($invoice->created_at ?? '') . '|' . (config('app.name') ?? '')), 0, 12);
        $appName = config('app.name', '1000PROXY');
    ?>
</head>
<body>
    <main class="invoice-container">
        <header>
            <div class="brand">
                <div class="brand-badge">1K</div>
                <div>
                    <div style="font-size:1rem; font-weight:800; color:#065f46; letter-spacing:0.5px;">{{ $appName }}</div>
                    <div class="small muted">Secure Proxy Services</div>
                </div>
            </div>
            <div style="text-align:right;">
                <h1>Invoice #{{ $invoice->id }}</h1>
                <div class="meta"><strong>Date:</strong> {{ optional($invoice->created_at)->format('Y-m-d') }}</div>
            </div>
        </header>

        <div class="grid">
            <div class="cell" style="width: 60%;">
                <div class="section-title">Billed To</div>
                <div class="meta"><span class="k">Customer:</span> {{ $customer->name }} ({{ $customer->email }})</div>
                @if(!empty($customer->phone))
                    <div class="meta"><span class="k">Phone:</span> {{ $customer->phone }}</div>
                @endif
            </div>
            <div class="cell" style="width: 40%;">
                <div class="section-title">Invoice</div>
                <div class="meta"><span class="k">Order ID:</span> #{{ $order->id }}</div>
                @if(!empty($invoice->iid))
                    <div class="meta"><span class="k">Invoice Ref:</span> {{ $invoice->iid }}</div>
                @endif
                <div class="meta"><span class="k">Status:</span>
                    <span class="status status-{{ strtolower($invoice->payment_status ?? 'pending') }}">{{ ucfirst($invoice->payment_status ?? 'Pending') }}</span>
                </div>
                <div class="meta small"><span class="k">Integrity Code:</span> <span class="code">{{ $documentIntegrity }}</span></div>
            </div>
        </div>

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
                        <td>${{ number_format((float) $invoice->price_amount, 2) }}</td>
                        <td>{{ strtoupper($invoice->price_currency ?? 'USD') }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="total">Total Due: ${{ number_format((float) $invoice->price_amount, 2) }} {{ strtoupper($invoice->price_currency ?? 'USD') }}</div>
        </section>

        <hr class="hr" />

        <section>
            <div class="section-title">Payment Details</div>
            <table>
                <tbody>
                    <tr>
                        <th style="width: 30%">Method</th>
                        <td>
                            {{ optional($invoice->paymentMethod)->name
                                ?? optional($order->paymentMethod)->name
                                ?? ucfirst($order->payment_method ?? 'N/A') }}
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="status status-{{ strtolower($invoice->payment_status ?? 'pending') }}">{{ ucfirst($invoice->payment_status ?? 'Pending') }}</span></td>
                    </tr>
                    @if(!empty($invoice->pay_currency) || !empty($invoice->pay_amount))
                    <tr>
                        <th>Pay Amount</th>
                        <td>{{ number_format((float) ($invoice->pay_amount ?? 0), 8) }} {{ strtoupper($invoice->pay_currency ?? '') }}</td>
                    </tr>
                    @endif
                    @if(!empty($invoice->amount_received))
                    <tr>
                        <th>Amount Received</th>
                        <td>{{ number_format((float) $invoice->amount_received, 8) }} {{ strtoupper($invoice->pay_currency ?? '') }}</td>
                    </tr>
                    @endif
                    @if(!empty($invoice->pay_address))
                    <tr>
                        <th>Pay Address</th>
                        <td><span class="code">{{ $invoice->pay_address }}</span></td>
                    </tr>
                    @endif
                    @if(!empty($invoice->network))
                    <tr>
                        <th>Network</th>
                        <td>{{ strtoupper($invoice->network) }}</td>
                    </tr>
                    @endif
                    @if(!empty($invoice->smart_contract))
                    <tr>
                        <th>Smart Contract</th>
                        <td><span class="code">{{ $invoice->smart_contract }}</span></td>
                    </tr>
                    @endif
                    @if(!empty($invoice->payin_extra_id))
                    <tr>
                        <th>Extra/Tag</th>
                        <td><span class="code">{{ $invoice->payin_extra_id }}</span></td>
                    </tr>
                    @endif
                    @if(!empty($invoice->payment_id))
                    <tr>
                        <th>Payment ID</th>
                        <td><span class="code">{{ $invoice->payment_id }}</span></td>
                    </tr>
                    @endif
                    @if(!empty($invoice->time_limit))
                    <tr>
                        <th>Time Limit</th>
                        <td>{{ $invoice->time_limit }} seconds</td>
                    </tr>
                    @endif
                    @if(!empty($invoice->expiration_estimate_date))
                    <tr>
                        <th>Expires By</th>
                        <td>{{ \Illuminate\Support\Carbon::parse($invoice->expiration_estimate_date)->format('Y-m-d H:i') }}</td>
                    </tr>
                    @endif
                    @if(optional($invoice)->walletTransaction)
                    <tr>
                        <th>Wallet Tx</th>
                        <td>ID: <span class="code">{{ $invoice->walletTransaction->id }}</span></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </section>

        <footer>
            <div><strong>{{ $appName }}</strong> — Privacy-first networking. Need help? Contact support via your dashboard.</div>
            <div class="small">This document is generated securely without remote assets. Verify integrity with code: <span class="code">{{ $documentIntegrity }}</span></div>
        </footer>
    </main>
</body>
</html>
