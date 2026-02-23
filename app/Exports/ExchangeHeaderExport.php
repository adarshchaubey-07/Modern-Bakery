<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExchangeHeaderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        $rows = [];

        $headers = ExchangeHeader::with([
            'country',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
        ])->get();

        foreach ($headers as $header) {
            $rows[] = [
                'Exchange Code'   => (string) $header->exchange_code,
                'Currency'        => (string) $header->currency,
                'Country Code'    => (string) ($header->country->country_code ?? ''),
                'Country Name'    => (string) ($header->country->country_name ?? ''),
                'Order Code'      => (string) ($header->order->order_code ?? ''),
                'Delivery Code'   => (string) ($header->delivery->delivery_code ?? ''),
                'Warehouse Code'  => (string) ($header->warehouse->warehouse_code ?? ''),
                'Warehouse Name'  => (string) ($header->warehouse->warehouse_name ?? ''),
                'Route Code'      => (string) ($header->route->route_code ?? ''),
                'Route Name'      => (string) ($header->route->route_name ?? ''),
                'Customer Code'   => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'   => (string) ($header->customer->name ?? ''),
                'Salesman Code'   => (string) ($header->salesman->osa_code ?? ''),
                'Salesman Name'   => (string) ($header->salesman->name ?? ''),
                'Gross Total'     => (float) $header->gross_total,
                'VAT'             => (float) $header->vat,
                'Net Amount'      => (float) $header->net_amount,
                'Total'           => (float) $header->total,
                'Discount'        => (float) $header->discount,
                'Status'          => $header->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Exchange Code',
            'Currency',
            'Country Code',
            'Country Name',
            'Order Code',
            'Delivery Code',
            'Warehouse Code',
            'Warehouse Name',
            'Route Code',
            'Route Name',
            'Customer Code',
            'Customer Name',
            'Salesman Code',
            'Salesman Name',
            'Gross Total',
            'VAT',
            'Net Amount',
            'Total',
            'Discount',
            'Status',
        ];
    }

    
       public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'F5F5F5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], 
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}