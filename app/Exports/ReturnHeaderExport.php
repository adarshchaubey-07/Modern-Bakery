<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ReturnHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReturnHeaderExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    public function collection()
    {
        $rows = [];

        $headers = ReturnHeader::with([
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
                'OSA Code'      => (string) $header->osa_code,
                'Order Code'    => (string) ($header->order->order_code ?? ''),
                'Delivery Code' => (string) ($header->delivery->delivery_code ?? ''),
                'Warehouse' => trim(
                    ($header->warehouse->warehouse_code ?? '') . ' - ' .
                    ($header->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($header->route->route_code ?? '') . ' - ' .
                    ($header->route->route_name ?? '')
                ),

                'Customer' => trim(
                    ($header->customer->osa_code ?? '') . ' - ' .
                    ($header->customer->name ?? '')
                ),

                'Salesman' => trim(
                    ($header->salesman->osa_code ?? '') . ' - ' .
                    ($header->salesman->name ?? '')
                ),

                'VAT'        => (float) $header->vat,
                'Net Amount' => (float) $header->net_amount,
                'Total'      => (float) $header->total,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Order Code',
            'Delivery Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Amount',
            'Total',
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
                        'bold'  => true,
                        'color' => ['rgb' => 'F5F5F5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
