<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Exchange #{{ $exchange->exchange_code }}</title>

    <style>
        body {
            font-family: "Inter", Helvetica, Arial, sans-serif;
            background: #f3f4f6;
            color: #333;
            margin: 0;
            padding: 30px;
        }

        .invoice-container {
            background: #fff;
            min-width: 1000px;
            min-height: 750px;
            margin: auto;
            border-radius: 12px;
            padding: 40px;
            border: 1px solid #e5e7eb;
        }

        header {
            display: flex;
            justify-content: flex-end;
            border-bottom: 2px solid #eef0f3;
            padding-bottom: 18px;
            margin-bottom: 30px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: #444;
        }

        .header-box {
            background: #fafafa;
            padding: 18px 20px;
            border-radius: 10px;
            border: 1px solid #ececec;
            margin-bottom: 25px;
        }

        .header-box p {
            margin: 4px 0;
            font-size: 14px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 12px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: #f0f2f5;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        td {
            padding: 12px;
            font-size: 14px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .totals-container {
            width: 35%;
            float: right;
            margin-top: 20px;
        }

        .totals-container table td {
            padding: 8px 0;
            font-size: 14px;
        }

        .totals-container tr:last-child td {
            font-size: 17px;
            font-weight: bold;
            border-top: 2px solid #d1d1d1;
        }
    </style>

</head>

<body>

    <div class="invoice-container">

        <header>
            <div class="invoice-title">
                <h2>EXCHANGE SUMMARY</h2>
            </div>
        </header>

        <!-- HEADER DETAILS -->
        <div class="header-box">
            <p><strong>Exchange Code:</strong> {{ $exchange->exchange_code }}</p>
            <p><strong>Customer:</strong> {{ $exchange->customer->name ?? '-' }}</p>
            <p><strong>Warehouse:</strong> {{ $exchange->warehouse->warehouse_name ?? '-' }}</p>
        </div>

        <!-- INVOICE ITEMS -->
        <div class="section-title">Invoice Items</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>VAT</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->uom->name }}</td>
                    <td>{{ $item->item_quantity }}</td>
                    <td>{{ number_format($item->item_price, 2) }}</td>
                    <td>{{ number_format($item->gross_total, 2) }}</td>
                    <td>{{ number_format($item->net_total, 2) }}</td>
                    <td>{{ number_format($item->VAT ?? 0, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- RETURN ITEMS -->
        <div class="section-title">Return Items</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>VAT</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($returnItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->uom->name }}</td>
                    <td>{{ $item->item_quantity }}</td>
                    <td>{{ number_format($item->item_price, 2) }}</td>
                    <td>{{ number_format($item->gross_total, 2) }}</td>
                    <td>{{ number_format($item->net_total, 2) }}</td>
                    <td>{{ number_format($item->VAT ?? 0, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</body>

</html>