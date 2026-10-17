<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #eef1f4;
            display: flex;
            justify-content: center;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
        }

        #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding: 3mm;
            margin: 0 auto;
            width: 58mm;               /* thermal receipt width — change to 58mm if that's your printer size */
            background: #FFF;
        }

        #invoice-POS ::selection { background: #f31544; color: #FFF; }

        #invoice-POS h1 { font-size: 1.5em; color: #222; margin: 0; }
        #invoice-POS h2 { font-size: .9em; margin: 0; }
        #invoice-POS h3 { font-size: 1.2em; font-weight: 300; line-height: 2em; margin: 0; }
        #invoice-POS p { font-size: .75em; color: #666; line-height: 1.3em; margin: 0; }

        #invoice-POS #top, #invoice-POS #mid, #invoice-POS #bot {
            border-bottom: 1px solid #EEE;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        #invoice-POS #bot { border-bottom: none; margin-bottom: 0; }

        #invoice-POS #top { min-height: 70px; text-align: center; }
        #invoice-POS #top .logo {
            height: 50px;
            width: 50px;
            margin: 0 auto 6px;
            background-size: 50px 50px;
            background-position: center;
            background-repeat: no-repeat;
        }

        #invoice-POS .info { display: block; margin-left: 0; }
        #invoice-POS #mid .info p { text-align: left; }

        #invoice-POS table { width: 100%; border-collapse: collapse; }
        #invoice-POS td { padding: 3px 0; }

        #invoice-POS .tabletitle { font-size: .68em; background: #EEE; }
        #invoice-POS .tabletitle td { padding: 4px 2px; }
        #invoice-POS .service { border-bottom: 1px solid #EEE; }
        #invoice-POS .item { width: 24mm; }
        #invoice-POS .itemtext { font-size: .72em; color: #333; }

        #invoice-POS .Rate, #invoice-POS .payment { text-align: right; }
        #invoice-POS .Hours { text-align: center; }

        #invoice-POS .grand-row td { border-top: 1px solid #333; }
        #invoice-POS .grand-row h2 { font-size: 1em; }

        #invoice-POS #legalcopy { margin-top: 5mm; }
        #invoice-POS .legal { text-align: center; font-size: .75em; }

        .no-print { text-align: center; margin-top: 18px; }
        .no-print button, .no-print a {
            padding: 8px 22px;
            border-radius: 6px;
            font-size: .9em;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-print { background: #1e2a38; color: #fff; border: none; }
        .btn-new { background: #fff; color: #333; border: 1px solid #ccc; margin-left: 8px; }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            #invoice-POS { box-shadow: none; width: 58mm; }
        }

    </style>
</head>
<body>

    <div id="invoice-POS">

        <center id="top">
            <div class="info">
                <h2>TG Surgicals</h2>
                <p>Surgical &amp; Medical Equipment</p>
            </div>
        </center><!--End InvoiceTop-->

        <div id="mid">
            <div class="info">
                <p>
                    Invoice #: {{ $sale->invoice_no }}<br>
                    Date: {{ $sale->created_at->format('d M Y, h:i A') }}<br>
                    Cashier: {{ $sale->user->name ?? '-' }}<br>
                    Customer: {{ $sale->customer->name ?? 'Walk-in Customer' }}<br>
                    Phone: {{ $sale->customer->phone ?? '-' }}
                </p>
            </div>
        </div><!--End Invoice Mid-->

        <div id="bot">
            <div id="table">
                <table>
                    <tr class="tabletitle">
                        <td class="item"><h2>Item</h2></td>
                        <td class="Hours"><h2>Qty</h2></td>
                        <td class="Rate"><h2>Total</h2></td>
                    </tr>

                    @foreach ($sale->items as $item)
                        <tr class="service">
                            <td class="tableitem"><p class="itemtext">{{ $item->product->name ?? 'N/A' }}</p></td>
                            <td class="tableitem" style="text-align:center;"><p class="itemtext">{{ $item->quantity }}</p></td>
                            <td class="tableitem Rate"><p class="itemtext">{{ number_format($item->total) }}</p></td>
                        </tr>
                    @endforeach

                    <tr class="tabletitle">
                        <td></td>
                        <td class="Rate"><h2>Sub Total</h2></td>
                        <td class="payment"><h2>Rs. {{ number_format($sale->sub_total) }}</h2></td>
                    </tr>
                    <tr class="tabletitle">
                        <td></td>
                        <td class="Rate"><h2>Discount</h2></td>
                        <td class="payment"><h2>Rs. {{ number_format($sale->discount) }}</h2></td>
                    </tr>
                    <tr class="tabletitle grand-row">
                        <td></td>
                        <td class="Rate"><h2>Total</h2></td>
                        <td class="payment"><h2>Rs. {{ number_format($sale->total) }}</h2></td>
                    </tr>
                    <tr class="tabletitle">
                        <td></td>
                        <td class="Rate"><h2>Paid</h2></td>
                        <td class="payment"><h2>Rs. {{ number_format($sale->paid_amount) }}</h2></td>
                    </tr>
                </table>
            </div><!--End Table-->

            <div id="legalcopy">
                <p class="legal"><strong>Thank you for your business!</strong></p>
            </div>
        </div><!--End InvoiceBot-->
    </div><!--End Invoice-->

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨 Print Bill</button>
        <a href="{{ route('pos.index') }}" class="btn-new">New Sale</a>
    </div>

    <script>
        // Dynamically set the exact page height to match the receipt content,
        // so Chrome's print dialog picks the correct custom paper size instead of "Letter".
        window.addEventListener('beforeprint', function () {
            const box = document.getElementById('invoice-POS');
            const heightPx = box.offsetHeight + 30; // small buffer
            const heightMm = Math.ceil(heightPx / 96 * 25.4); // px -> mm (96dpi)

            let styleTag = document.getElementById('dynamic-page-size');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-page-size';
                document.head.appendChild(styleTag);
            }
            styleTag.innerHTML = `@page { size: 58mm ${heightMm}mm; margin: 0; }`;
        });
    </script>

</body>
</html>