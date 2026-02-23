<?php

namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderHeaderFullExport implements FromCollection, WithHeadings, ShouldAutoSize,WithStyles
{
    public function collection()
    {
        $rows = [];

        $orders = OrderHeader::with(['warehouse', 'customer'])->get();

        foreach ($orders as $order) {
            $rows[] = [
                'Order Code'      => (string) $order->order_code,
                'Warehouse Name'  => (string) ($order->warehouse->warehouse_name ?? ''),
                'Customer Name'   => (string) ($order->customer->name ?? ''),
                'Delivery Date'   => (string) ($order->delivery_date?->format('Y-m-d') ?? ''),
                'Comment'         => (string) ($order->comment ?? ''),
                'Status'          => $order->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Warehouse Name',
            'Customer Name',
            'Delivery Date',
            'Comment',
            'Status',
        ];
    }

         public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    }
}