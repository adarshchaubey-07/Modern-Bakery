<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Inter", Arial, sans-serif;
            background: #f3f4f6;
            color: #333;
            padding: 20px;
            font-size: 12px;
            line-height: 1.3;
        }

        .invoice-container {
            background: #fff;
            max-width: 900px;
            margin: auto;
            padding: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        header {
            text-align: right;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 11px;
            color: #555;
        }

        .address-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .address-box {
            display: table-cell;
            width: 50%;
            background: #fafafa;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #ececec;
            vertical-align: top;
        }

        .address-box h4 {
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .address-box p {
            font-size: 11px;
            line-height: 1.2;
        }

        .table-box {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th {
            background: #f0f2f5;
            padding: 8px;
            font-weight: 600;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #eee;
            background: #fff;
            font-size: 11px;
            text-align: left;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals {
            max-width: 260px;
            margin-left: auto;
            margin-top: 20px;
        }

        .totals td {
            padding: 5px 0;
        }

        .totals tr:last-child td {
            font-size: 14px;
            font-weight: 700;
            border-top: 1px solid #666;
            padding-top: 8px;
        }

        .note {
            margin-top: 25px;
            padding: 10px;
            background: #fafafa;
            border-left: 3px solid #ccc;
            font-size: 11px;
        }

        .footer-text {
            margin-top: 10px;
            font-size: 11px;
            font-weight: 600;
        }

    </style>
</head>

<body>

<div class="invoice-container">

    <!-- HEADER -->
    <header>
        <div class="invoice-title">
            <h2>INVOICE / TAX INVOICE</h2>
            <span>{{ $header->invoice_code }}{{ $header->invoice_number }}</span><br>
            <span>{{ \Carbon\Carbon::parse($header->invoice_date)->format('d M Y') }}</span>
        </div>
    </header>

    <!-- ADDRESS SECTION -->
    <div class="address-section">
        <div class="address-box">
            <h4>Seller</h4>
            <p>
                <strong>{{ $header->warehouse->warehouse_name }}</strong><br>
                {{ $header->warehouse->city }}<br>
                Phone: {{ $header->warehouse->warehouse_manager_contact }}<br>
                TIN: {{ $header->warehouse->tin_no }}
            </p>
        </div>

        <div class="address-box">
            <h4>Buyer</h4>
            <p>
                <strong>{{ $header->customer->name }}</strong><br>
                {{ $header->customer->district }}<br>
                Phone: {{ $header->customer->contact_no }}<br>
                OSA Code: {{ $header->customer->osa_code }}<br>
                TIN: {{ $header->customer->vat_no }}
            </p>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="table-box">
        <table>
            <thead>
            <tr>
                <th style="width: 35px;">#</th>
                <th>Item Name</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 60px;">UOM</th>
                <th style="width: 80px;" class="text-right">Price</th>
                <th style="width: 100px;" class="text-right">Total (UGX)</th>
            </tr>
            </thead>

            <tbody>
            @foreach($details as $i => $d)
                <tr>
                  <td class="text-center">{{ $i + 1 }}</td>

                <td class="text-left">{{ $d->item->name ?? '' }}</td>

                <td class="text-center">{{ number_format($d->quantity, 0) }}</td>

                <td class="text-center">{{ optional($d->itemuom)->name ?? '' }}</td>

                <td class="text-right">
                    {{ number_format(optional($d->itemuom)->price ?? 0, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format( ($d->quantity ?? 0) * (optional($d->itemuom)->price ?? 0), 2) }}
                </td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- TOTALS -->
    <div class="totals">
        <table>
            <tr><td>VAT</td><td class="text-right">{{ number_format($header->vat, 2) }}</td></tr>
            <tr><td>NET</td><td class="text-right">{{ number_format($header->net_total, 2) }}</td></tr>
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-right"><strong>{{ number_format($header->total_amount, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    @if($header->ura_qr_code)
        <div class="text-center" style="margin-top:30px;">
            <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl={{ urlencode($header->ura_qr_code) }}">
        </div>
    @endif

    <div class="note">
        <strong>Note:</strong> Invoice value is inclusive of 18% VAT.
    </div>

    <div class="footer-text">
        This is a system-generated invoice and does not require a signature.
    </div>

</div>

</body>
</html>
