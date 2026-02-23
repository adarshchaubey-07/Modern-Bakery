<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Return #{{ $return->uuid }}</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #333;
            padding: 30px;
        }

        .invoice-container {
            /* width: 900px; */
            background: #fff;
            margin: auto;
            /* padding: 35px; */
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        /** HEADER **/
        header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #dcdcdc;
            margin-bottom: 25px;
        }

        header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #444;
        }

        /** ADDRESS SECTION **/
        .address-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .address-box {
            display: table-cell;
            width: 50%;
            padding: 15px;
            background: #fafafa;
            border: 1px solid #e2e2e2;
            vertical-align: top;
        }

        .address-box h4 {
            margin: 0 0 8px 0;
            font-size: 15px;
            font-weight: bold;
        }

        .address-box p {
            margin: 0;
            line-height: 1.3;
            font-size: 12px;
        }

        /** TABLE WRAPPER FOR BORDER RADIUS **/
        .table-box {
            width: 95%;
            border: 1px solid #cfcfcf;
            border-radius: 10px;
            overflow: hidden;
            margin: 0 auto;
            margin-top: 15px;
        }

        /** DATA TABLE **/
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f0f0f0;
            padding: 6px;
            font-size: 11px;
            border: 1px solid #d0d0d0;
            font-weight: bold;
            text-align: center;
        }

        .data-table td {
            padding: 6px;
            font-size: 11px;
            border: 1px solid #e0e0e0;
            text-align: center;
            word-break: break-word;
        }

        /** FIXED COLUMN WIDTHS **/
        /* .data-table th:nth-child(1), .data-table td:nth-child(1) { width: 30px; }
        .data-table th:nth-child(2), .data-table td:nth-child(2) { width: 80px; }
        .data-table th:nth-child(3), .data-table td:nth-child(3) { width: 180px; }
        .data-table th:nth-child(4), .data-table td:nth-child(4) { width: 50px; }
        .data-table th:nth-child(5), .data-table td:nth-child(5) { width: 40px; }
        .data-table th:nth-child(6), .data-table td:nth-child(6) { width: 60px; }
        .data-table th:nth-child(7), .data-table td:nth-child(7) { width: 60px; }
        .data-table th:nth-child(8), .data-table td:nth-child(8) { width: 60px; }
        .data-table th:nth-child(9), .data-table td:nth-child(9) { width: 60px; }
        .data-table th:nth-child(10), .data-table td:nth-child(10) { width: 50px; }
        .data-table th:nth-child(11), .data-table td:nth-child(11) { width: 70px; } */

        /** TOTALS **/
        .totals-wrapper {
            width: 100%;
            margin-top: 20px;
            position: relative;
            height: 200px;
            /* display: flex;
            justify-content: flex-end; */
        }

        .totals-table {
            width: 280px;
            position: absolute;
            right: 0;
            margin-right: 17px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 8px;
            font-size: 12px;
            border: 1px solid #d0d0d0;
        }

        .totals-table tr:last-child td {
            font-weight: bold;
            background: #f7f7f7;
        }

        /** NOTE **/
        .note {
            margin-top: 30px;
            padding: 12px;
            border-left: 4px solid #999;
            background: #fafafa;
            font-size: 13px;
        }

    </style>
</head>

<body>

<div class="invoice-container">

    <!-- HEADER -->
    <header>
        <h2>RETURN</h2>
        <span>{{ $return->osa_code }}</span>
    </header>

    <!-- ADDRESS SECTION -->
    <div class="address-section" style="margin: 0 auto; width: 95% !important;">
        <div class="address-box">
            <h4>Seller</h4>
            <p>
                <strong>{{ $return->warehouse->warehouse_name ?? '' }}</strong><br>
                {{ $return->warehouse->city ?? '' }}<br>
                Phone: {{ $return->warehouse->warehouse_manager_contact ?? '' }}<br>
                TIN: {{ $return->warehouse->tin_no ?? '' }}
            </p>
        </div>

        <div class="address-box" style="margin-left: 25px;">
            <h4>Buyer</h4>
            <p>
                <strong>{{ $return->customer->name ?? '' }}</strong><br>
                {{ $return->customer->district ?? '' }}<br>
                Phone: {{ $return->customer->contact_no ?? '' }}<br>
                OSA Code: {{ $return->customer->osa_code ?? '' }}
            </p>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="table-box">
        <table class="data-table" >
            <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Gross</th>
                <th>Net</th>
                <th>VAT</th>
                <th>Total</th>
            </tr>
            </thead>

            <tbody>
            @foreach($returnDetails as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item->code }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->uom->name }}</td>
                    <td>{{ $item->item_quantity }}</td>
                    <td>{{ number_format($item->item_price, 2) }}</td>
                    <td>{{ number_format($item->discount, 2) }}</td>
                    <td>{{ number_format($item->gross_total, 2) }}</td>
                    <td>{{ number_format($item->net_total, 2) }}</td>
                    <td>{{ number_format($item->vat, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- TOTALS -->
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr><td>Gross Total</td><td>AED {{ number_format($return->gross_total, 2) }}</td></tr>
            <tr><td>Discount</td><td>AED {{ number_format($return->discount_total, 2) }}</td></tr>
            <tr><td>Net Total</td><td>AED {{ number_format($return->net_total, 2) }}</td></tr>
            <tr><td>VAT</td><td>AED {{ number_format($return->vat, 2) }}</td></tr>
            <tr><td>Total</td><td>AED {{ number_format($return->total, 2) }}</td></tr>
        </table>
    </div>

    <!-- NOTE -->
    <div class="note">
        <strong>Note:</strong> {{ $return->note ?? 'No notes added.' }}
    </div>

</div>

</body>
</html>
