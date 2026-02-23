<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LoadHeaderFullExport implements FromCollection, WithHeadings, ShouldAutoSize,WithEvents
{
    public function collection()
    {
        $rows = [];

        $loads = LoadHeader::with(['warehouse', 'route', 'salesman', 'projecttype'])->get();

        foreach ($loads as $load) {
            $rows[] = [
                'OSA Code'        => (string) $load->osa_code,
                'Salesman Type'   => (string) ($load->salesman_type ?? ''),
                'Warehouse Name'  => (string) ($load->warehouse->warehouse_name ?? ''),
                'Route Name'      => (string) ($load->route->route_name ?? ''),
                'Salesman Name'   => (string) ($load->salesman->name ?? ''),
                'Project Type'    => (string) ($load->projecttype->salesman_type_name ?? ''),
                'Is Confirmed'    => $load->is_confirmed ? 'Yes' : 'No',
                'Accept Time'     => (string) ($load->accept_time ?? ''),
                'Salesman Sign'   => (string) ($load->salesman_sign ?? ''),
                'Longitude'       => (string) ($load->longtitude ?? ''),
                'Latitude'        => (string) ($load->latitude ?? ''),
                'Status'          => $load->status == 1 ? 'Active' : 'Inactive',
            ];
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
            'Longitude',
            'Latitude',
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