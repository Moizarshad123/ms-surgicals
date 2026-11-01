<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #eef1f4;
            display: flex;
            justify-content: center;
            padding: 20px 0;
            font-family: 'Courier New', Courier, monospace;
        }

        #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding: 4mm 3mm;
            margin: 0 auto;
            width: 72mm;
            background: #FFF;
        }

        /* ---- Top (Logo + Business Info) ---- */
        #top {
            text-align: center;
            padding-bottom: 6px;
            margin-bottom: 6px;
            border-bottom: 1px dashed #ccc;
        }
        #top img {
            display: block;
            margin: 0 auto 5px;
            max-width: 80px;
            height: auto;
        }
        #top .biz-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111;
        }
        #top .biz-sub {
            font-size: 9px;
            color: #555;
            line-height: 1.4;
            margin-top: 2px;
        }
        #top .receipt-label {
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }

        /* ---- Mid (Invoice meta) ---- */
        #mid {
            font-size: 9px;
            color: #333;
            line-height: 1.6;
            padding-bottom: 6px;
            margin-bottom: 6px;
            border-bottom: 1px dashed #ccc;
        }
        #mid .meta-row {
            display: flex;
            justify-content: space-between;
        }
        #mid .meta-label { color: #666; }
        #mid .meta-val { font-weight: bold; text-align: right; }

        /* ---- Items Table ---- */
        #items-section {
            padding-bottom: 6px;
            margin-bottom: 4px;
            border-bottom: 1px dashed #ccc;
        }
        .items-header {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #ddd;
            margin-bottom: 2px;
        }
        .item-row {
            font-size: 9px;
            display: flex;
            align-items: flex-start;
            padding: 2px 0;
            border-bottom: 1px dotted #eee;
        }
        .item-name { flex: 1; color: #222; line-height: 1.3; padding-right: 3px; }
        .item-qty  { width: 22px; text-align: center; color: #444; flex-shrink: 0; }
        .item-price { width: 38px; text-align: right; color: #444; flex-shrink: 0; }
        .item-disc { width: 24px; text-align: center; color: #444; flex-shrink: 0; }
        .item-total { width: 44px; text-align: right; font-weight: bold; color: #111; flex-shrink: 0; }

        /* ---- Totals ---- */
        #totals-section {
            font-size: 9.5px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            color: #333;
        }
        .total-row.grand {
            font-size: 11px;
            font-weight: bold;
            color: #111;
            border-top: 1px solid #333;
            padding-top: 4px;
            margin-top: 2px;
        }
        .total-row.balance {
            font-size: 10px;
            font-weight: bold;
            color: #c0392b;
            border-top: 1px dashed #ccc;
            margin-top: 4px;
            padding-top: 4px;
        }
        .total-row.paid-row { color: #27ae60; font-weight: 600; }
        .total-label { color: inherit; }
        .total-val { text-align: right; font-weight: inherit; }

        /* ---- Footer ---- */
        #footer {
            text-align: center;
            margin-top: 8px;
            font-size: 8.5px;
            color: #777;
            border-top: 1px dashed #ccc;
            padding-top: 6px;
        }

        /* ---- Print ---- */
        .no-print { text-align: center; margin-top: 18px; }
        .no-print button, .no-print a {
            padding: 8px 22px; border-radius: 6px;
            font-size: .9em; text-decoration: none;
            cursor: pointer; display: inline-block;
        }
        .btn-print { background: #1e2a38; color: #fff; border: none; }
        .btn-new { background: #fff; color: #333; border: 1px solid #ccc; margin-left: 8px; }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            #invoice-POS { box-shadow: none; width: 72mm; }
        }
    </style>
</head>
<body>

    <div id="invoice-POS">

        {{-- TOP: Logo + Business Info --}}
        <div id="top">
            <img src="{{ asset('admin/images/logo-2.png') }}" alt="TG Surgicals" onerror="this.style.display='none'">
            <div class="biz-name">TG Surgicals</div>
            <div class="biz-sub">
                Shop 58, Mirwah Road TandoAllahyar<br>
                Phone: 0334-2830393
            </div>
            <div class="receipt-label">— Sale Receipt —</div>
        </div>

        {{-- MID: Invoice Meta --}}
        <div id="mid">
            <div class="meta-row"><span class="meta-label">Invoice #</span><span class="meta-val">{{ $sale->invoice_no }}</span></div>
            <div class="meta-row"><span class="meta-label">Date</span><span class="meta-val">{{ $sale->created_at->format('d M Y, h:i A') }}</span></div>
            <div class="meta-row"><span class="meta-label">Cashier</span><span class="meta-val">{{ $sale->user->name ?? '-' }}</span></div>
            <div class="meta-row"><span class="meta-label">Customer</span><span class="meta-val">{{ $sale->customer->name ?? 'Walk-in' }}</span></div>
            @if($sale->customer?->phone)
            <div class="meta-row"><span class="meta-label">Phone</span><span class="meta-val">{{ $sale->customer->phone }}</span></div>
            @endif
            <div class="meta-row"><span class="meta-label">Status</span><span class="meta-val" style="color:{{ $sale->payment_status === 'paid' ? '#27ae60' : '#c0392b' }};">{{ strtoupper($sale->payment_status) }}</span></div>
        </div>

        {{-- ITEMS --}}
        @php
            $itemCount = $sale->items->count();
            $totalQty  = $sale->items->sum('quantity');
        @endphp
        <div id="items-section">
            <div class="items-header">
                <span style="flex:1;">Item</span>
                <span style="width:22px;text-align:center;">Qty</span>
                <span style="width:38px;text-align:right;">Price</span>
                <span style="width:24px;text-align:center;">%</span>
                <span style="width:44px;text-align:right;">Total</span>
            </div>
            @foreach ($sale->items as $item)
                @php
                    $discP = (float)($item->discount_percent ?? 0);
                    $discA = (float)($item->discount_amount ?? 0);
                @endphp
                <div class="item-row">
                    <span class="item-name">
                        {{ $item->product->name ?? 'N/A' }}
                        @if($discA > 0)
                            <div style="font-size:8px; color:#666;">Disc: -Rs. {{ number_format($discA, 2) }}</div>
                        @endif
                    </span>
                    <span class="item-qty">{{ $item->quantity }}</span>
                    <span class="item-price">{{ number_format((float)$item->price, 2) }}</span>
                    <span class="item-disc">{{ $discP > 0 ? rtrim(rtrim(number_format($discP, 2), '0'), '.') . '%' : '-' }}</span>
                    <span class="item-total">{{ number_format((float)$item->total, 2) }}</span>
                </div>
            @endforeach
        </div>

        {{-- TOTALS --}}
        @php
            $currentDue      = max(0, (float) $sale->total - (float) $sale->paid_amount);
            $previousBalance = 0;
            if ($sale->customer_id) {
                $previousBalance = (float) \App\Models\Sale::where('customer_id', $sale->customer_id)
                    ->where('id', '<', $sale->id)
                    ->whereRaw('total > paid_amount')
                    ->sum(\Illuminate\Support\Facades\DB::raw('total - paid_amount'));
                $previousBalance = max(0, $previousBalance);
            }
            $totalNetDue = $previousBalance + $currentDue;
        @endphp
        <div id="totals-section">
            <div class="total-row">
                <span class="total-label">Items ({{ $itemCount }} types / {{ $totalQty }} qty)</span>
                <span class="total-val">Rs. {{ number_format((float)$sale->sub_total, 2) }}</span>
            </div>
            @if($sale->discount > 0)
            <div class="total-row">
                <span class="total-label">Discount</span>
                <span class="total-val" style="color:#e67e22;">- Rs. {{ number_format((float)$sale->discount, 2) }}</span>
            </div>
            @endif
            <div class="total-row grand">
                <span class="total-label">Inv Total</span>
                <span class="total-val">Rs. {{ number_format((float)$sale->total, 2) }}</span>
            </div>
            @if($sale->paid_amount > 0)
            <div class="total-row paid-row">
                <span class="total-label">{{ $currentDue > 0 ? 'Paid Today' : 'Paid' }}</span>
                <span class="total-val">Rs. {{ number_format((float)$sale->paid_amount, 2) }}</span>
            </div>
            @endif
            @if($currentDue > 0)
            <div class="total-row" style="color:#c0392b;">
                <span class="total-label">Remaining Due</span>
                <span class="total-val">Rs. {{ number_format($currentDue, 2) }}</span>
            </div>
            @endif
            @if($sale->customer_id && $previousBalance > 0)
            <div class="total-row balance">
                <span class="total-label">Prev. Balance</span>
                <span class="total-val">Rs. {{ number_format($previousBalance, 2) }}</span>
            </div>
            <div class="total-row balance" style="font-size:11px;">
                <span class="total-label">Total Net Due</span>
                <span class="total-val">Rs. {{ number_format($totalNetDue, 2) }}</span>
            </div>
            @endif
        </div>

        {{-- FOOTER --}}
        <div id="footer">
            <strong>Thank you for your business!</strong><br>
            Please keep this receipt for your records.
        </div>

    </div><!--End Invoice-->

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨 Print Bill</button>
        <a href="{{ route('pos.index') }}" class="btn-new">New Sale</a>
    </div>

    <script>
        window.addEventListener('beforeprint', function () {
            const box = document.getElementById('invoice-POS');
            const heightPx = box.offsetHeight + 30;
            const heightMm = Math.ceil(heightPx / 96 * 25.4);

            let styleTag = document.getElementById('dynamic-page-size');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-page-size';
                document.head.appendChild(styleTag);
            }
            styleTag.innerHTML = `@page { size: 72mm ${heightMm}mm; margin: 0; }`;
        });
    </script>

</body>
</html>
