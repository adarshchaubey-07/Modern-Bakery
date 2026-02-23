<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\LoadDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LoadCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $groupIndexes = [];

    public function collection()
    {
        $rows = [];
        $rowIndex = 2; // headings row

        $headers = LoadHeader::with(['warehouse', 'route', 'salesman', 'projecttype'])->get();

        foreach ($headers as $header) {
            $headerRowIndex = $rowIndex;

            $rows[] = [
                'OSA Code'       => (string) $header->osa_code,
                'Salesman Type'  => (string) ($header->salesman_type ?? ''),
                'Warehouse Name' => (string) ($header->warehouse->warehouse_name ?? ''),
                'Route Name'     => (string) ($header->route->route_name ?? ''),
                'Salesman Name'  => (string) ($header->salesman->name ?? ''),
                'Project Type'   => (string) ($header->projecttype->salesman_type_name ?? ''),
                'Is Confirmed'   => $header->is_confirmed ? 'Yes' : 'No',
                'Accept Time'    => (string) ($header->accept_time ?? ''),
                'Salesman Sign'  => (string) ($header->salesman_sign ?? ''),
                'Latitude'       => (string) ($header->latitude ?? ''),
                'Longitude'      => (string) ($header->longtitude ?? ''),
                'Status'         => $header->status == 1 ? 'Active' : 'Inactive',

                'Item Code'      => '',
                'Item Name'      => '',
                'UOM Name'       => '',
                'Quantity'       => '',
                'Price'          => '',
                'Detail Status'  => '',
            ];

            $rowIndex++;

            $details = LoadDetail::with(['item', 'itemUom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
                    'OSA Code'       => '',
                    'Salesman Type'  => '',
                    'Warehouse Name' => '',
                    'Route Name'     => '',
                    'Salesman Name'  => '',
                    'Project Type'   => '',
                    'Is Confirmed'   => '',
                    'Accept Time'    => '',
                    'Salesman Sign'  => '',
                    'Latitude'       => '',
                    'Longitude'      => '',
                    'Status'         => '',

                    'Item Code'      => (string) ($detail->item->code ?? ''),
                    'Item Name'      => (string) ($detail->item->name ?? ''),
                    'UOM Name'       => (string) ($detail->itemUom->name ?? ''),
                    'Quantity'       => (float) ($detail->qty ?? 0),
                    'Price'          => (float) ($detail->price ?? 0),
                    'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
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

            // add empty row after each header group
            $rows[] = array_fill_keys($this->headings(), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Salesman Type',
            'Warehouse Name',
            'Route Name',
            'Salesman Name',
            'Project Type',
            'Is Confirmed',
            'Accept Time',
            'Salesman Sign',
            'Latitude',
            'Longitude',
            'Status',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Price',
            'Detail Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // header style
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

                // collapse detail rows
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