<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class SalesmanInvoicesExport implements FromCollection, WithHeadings
{
    protected $salesmanId;

    public function __construct($salesmanId)
    {
        $this->salesmanId = $salesmanId;
    }

    public function collection()
    {
        return InvoiceHeader::with(['warehouse:id,warehouse_name'])
            ->where('salesman_id', $this->salesmanId)
            ->get([
                'invoice_number',
                'invoice_date',
                'total_amount',
                'status',
                'warehouse_id',
                'created_at',
            ])
            ->map(function ($invoice) {
                return [
                    'Invoice Number' => $invoice->invoice_number,
                    'Invoice Date'   => $invoice->invoice_date,
                    'Total Amount'   => $invoice->total_amount,
                    'Status'         => ucfirst($invoice->status),
                    'Warehouse'      => optional($invoice->warehouse)->warehouse_name,
                    'Created At'     => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
            'Total Amount',
            'Status',
            'Warehouse',
            'Created At',
        ];
    }
}
