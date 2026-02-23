<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Inter", Arial, sans-serif;
            color: #333;
            background: #f3f4f6;
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

        /* HEADER */
        header {
            text-align: right;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 12px;
            color: #555;
        }

        /* ADDRESS SECTION */
        .address-section {
            width: 100%;
            display: table;
            margin-bottom: 20px;
        }

        .address-box {
            display: table-cell;
            width: 50%;
            background: #fafafa;
            padding: 14px;
            border: 1px solid #ececec;
            border-radius: 8px;
            vertical-align: top;
        }

        .address-box h4 {
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 700;
        }

        .address-box p {
            font-size: 11px;
            line-height: 1.2;
        }

        /* ITEMS TABLE */
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
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            text-align: center;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #eee;
            background: #fff;
            text-align: center;
        }

        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }

        /* TOTALS + PAYMENT SECTION */
        .details-box {
            width: 100%;
            margin-top: 20px;
            display: table;
        }

        .details-left,
        .details-right {
            display: table-cell;
            vertical-align: top;
            padding: 10px;
        }

        .details-left {
            width: 50%;
            font-size: 12px;
        }

        .details-right {
            width: 50%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .totals-table td {
            padding: 6px 4px;
        }

        .totals-table tr:last-child td {
            font-weight: bold;
            border-top: 1px solid #aaa;
            padding-top: 8px;
        }

        /* NOTE */
        .note {
            margin-top: 25px;
            padding: 10px;
            background: #fafafa;
            border-left: 3px solid #ccc;
            font-size: 11px;
        }

    </style>

</head>

<body>

<div class="invoice-container">

    <!-- HEADER -->
    <header>
        <div class="invoice-title">
            <h2>DELIVERY</h2>
            <span>{{ $delivery->delivery_code ?? '' }}</span>
        </div>
    </header>

    <!-- ADDRESS SECTION -->
    <div class="address-section">
        <div class="address-box">
            <h4>Seller</h4>
            <p>
                <strong>{{ $delivery->warehouse->warehouse_name ?? '' }}</strong><br>
                {{ $delivery->warehouse->address ?? '' }}<br>
                Phone: {{ $delivery->warehouse->owner_number ?? '' }}<br>
                Email: {{ $delivery->warehouse->owner_email ?? '' }}<br>
                OSA Code: {{ $delivery->warehouse->warehouse_code ?? '' }}
            </p>
        </div>

        <div class="address-box">
            <h4>Buyer</h4>
            <p>
                <strong>{{ $delivery->customer->name ?? '' }}</strong><br>
                {{ $delivery->customer->district ?? '' }} ({{ $delivery->customer->town ?? '' }})<br>
                Phone: {{ $delivery->customer->contact_no ?? '' }}<br>
                OSA Code: {{ $delivery->customer->osa_code ?? '' }}
            </p>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="table-box">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Net</th>
                <th>VAT</th>
                <th>Total</th>
            </tr>
            </thead>

            <tbody>
            @foreach($deliveryDetails as $i => $item)
                <tr>
                     <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item->code ?? '' }}</td>
                    <td>{{ $item->item->name ?? '' }}</td>
                    <td>{{ $item->Uom->name ?? '' }}</td>
                    <td>{{ number_format($item->quantity ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($item->item_price ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item->net_total ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item->vat ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total ?? 0, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- PAYMENT + TOTALS -->
    <div class="details-box">

        <!-- RIGHT SIDE -->
        <div class="details-right">
            <table class="totals-table">
                                <tr>
                    <td>VAT</td>
                    <td class="text-right">{{ number_format($delivery->vat ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Net Total</td>
                    <td class="text-right">{{ number_format($delivery->net_amount ?? 0, 2) }}</td>
                </tr>
                    <td>Total</td>
                    <td class="text-right"><b>{{ number_format($delivery->total ?? 0, 2) }}</b></td>
                </tr>
            </table>
        </div>

    </div>

</div>

</body>
</html>
