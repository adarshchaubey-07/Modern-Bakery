<?php

namespace App\Exports;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CapsCollectionFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    public function collection()
    {
        $rows = [];

        $headers = CapsCollectionHeader::with([
            'warehouse',
            'route',
            'customerdata'
        ])->get();

        foreach ($headers as $header) {

            $rows[] = [
                'CapsCollection Code' => (string) $header->code,
                'Warehouse' => trim(
                    ($header->warehouse->warehouse_code ?? '') . '-' .
                    ($header->warehouse->warehouse_name ?? '')
                ),
                'Customer' => trim(
                    ($header->customerdata->osa_code ?? '') . '-' .
                    ($header->customerdata->name ?? '')
                ),
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'CapsCollection Code',
            'Warehouse',
            'Customer',
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
