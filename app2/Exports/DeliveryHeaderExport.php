<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DeliveryHeaderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        $rows = [];

        $headers = AgentDeliveryHeaders::with([
            'warehouse',
            'route',
            'salesman',
            'customer',
            'country',
        ])->get();

        foreach ($headers as $header) {
            $rows[] = [
                'Delivery Code'  => (string) ($header->delivery_code ?? ''),
                'Warehouse Name' => (string) ($header->warehouse->warehouse_name ?? ''),
                'Route Name'     => (string) ($header->route->route_name ?? ''),
                'Salesman Name'  => (string) ($header->salesman->name ?? ''),
                'Customer Name'  => (string) ($header->customer->name ?? ''),
                'Country Name'   => (string) ($header->country->country_name ?? ''),
                'Currency'       => (string) ($header->country->currency ?? ''),
                'Gross Total'    => (float) ($header->gross_total ?? 0),
                'VAT'            => (float) ($header->vat ?? 0),
                'Discount'       => (float) ($header->discount ?? 0),
                'Net Amount'     => (float) ($header->net_amount ?? 0),
                'Total'          => (float) ($header->total ?? 0),
                'Delivery Date'  => (string) ($header->delivery_date ?? ''),
                'Comment'        => (string) ($header->comment ?? ''),
                'Status'         => (string) ($header->status == 1 ? 'Active' : 'Inactive'),
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Delivery Code',
            'Warehouse Name',
            'Route Name',
            'Salesman Name',
            'Customer Name',
            'Country Name',
            'Currency',
            'Gross Total',
            'VAT',
            'Discount',
            'Net Amount',
            'Total',
            'Delivery Date',
            'Comment',
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
                        'color' => ['rgb' => 'FFFFFF'],
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
