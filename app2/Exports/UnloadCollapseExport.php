<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\UnloadDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnloadCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $headers = UnloadHeader::with(['warehouse', 'route', 'salesman', 'projecttype'])->get();
        
        foreach ($headers as $header) {
            $headerRowIndex = $rowIndex;
            $rows[] = [
                'OSA Code'       => (string)($header->osa_code ?? ''),
                'Unload No'      => (string)($header->unload_no ?? ''),
                'Unload Date'    => (string)($header->unload_date ?? ''),
                'Unload Time'    => (string)($header->unload_time ?? ''),
                'Sync Date'      => (string)($header->sync_date ?? ''),
                'Sync Time'      => (string)($header->sync_time ?? ''),
                'Salesman Type'  => (string)($header->salesman_type ?? ''),
                'Warehouse Name' => (string)($header->warehouse->warehouse_name ?? ''),
                'Route Name'     => (string)($header->route->route_name ?? ''),
                'Salesman Name'  => (string)($header->salesman->name ?? ''),
                'Project Type'   => (string)($header->projecttype->salesman_type_name ?? ''),
                'Latitude'       => (string)($header->latitude ?? ''),
                'Longitude'      => (string)($header->longtitude ?? ''),
                'Unload From'    => (string)($header->unload_from ?? ''),
                'Load Date'      => (string)($header->load_date ?? ''),
                'Status'         => (string)($header->status == 1 ? 'Active' : 'Inactive'),

                'Item Code'      => '',
                'Item Name'      => '',
                'UOM Name'       => '',
                'Quantity'       => '',
                'Detail Status'  => '',
            ];

            $rowIndex++;
            $details = UnloadDetail::with(['item', 'itemuom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            // Detail rows
            foreach ($details as $detail) {
                $rows[] = [
                    'OSA Code'       => '',
                    'Unload No'      => '',
                    'Unload Date'    => '',
                    'Unload Time'    => '',
                    'Sync Date'      => '',
                    'Sync Time'      => '',
                    'Salesman Type'  => '',
                    'Warehouse Name' => '',
                    'Route Name'     => '',
                    'Salesman Name'  => '',
                    'Project Type'   => '',
                    'Latitude'       => '',
                    'Longitude'      => '',
                    'Unload From'    => '',
                    'Load Date'      => '',
                    'Status'         => '',

                    'Item Code'      => (string)($detail->item->code ?? ''),
                    'Item Name'      => (string)($detail->item->name ?? ''),
                    'UOM Name'       => (string)($detail->itemuom->name ?? ''),
                    'Quantity'       => (float)($detail->qty ?? 0),
                    'Detail Status'  => (string)($detail->status == 1 ? 'Active' : 'Inactive'),
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }

            if (count($detailRowIndexes) > 0) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            $rows[] = array_fill_keys(array_keys($rows[0]), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Unload No',
            'Unload Date',
            'Unload Time',
            'Sync Date',
            'Sync Time',
            'Salesman Type',
            'Warehouse Name',
            'Route Name',
            'Salesman Name',
            'Project Type',
            'Latitude',
            'Longitude',
            'Unload From',
            'Load Date',
            'Status',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Detail Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:U1')->getFont()->setBold(true);
        $sheet->getStyle('A1:U1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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

                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}