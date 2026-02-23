<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnloadHeaderFullExport implements FromCollection, WithHeadings, ShouldAutoSize,WithEvents
{
    public function collection()
    {
        $rows = [];

        $unloads = UnloadHeader::with(['warehouse', 'route', 'salesman', 'projecttype'])->get();

        foreach ($unloads as $unload) {
            $rows[] = [
                'OSA Code'        => (string) ($unload->osa_code ?? ''),
                'Unload No'       => (string) ($unload->unload_no ?? ''),
                'Unload Date'     => (string) ($unload->unload_date ?? ''),
                'Unload Time'     => (string) ($unload->unload_time ?? ''),
                'Sync Date'       => (string) ($unload->sync_date ?? ''),
                'Sync Time'       => (string) ($unload->sync_time ?? ''),
                'Salesman Type'   => (string) ($unload->salesman_type ?? ''),
                'Warehouse Name'  => (string) ($unload->warehouse->warehouse_name ?? ''),
                'Route Name'      => (string) ($unload->route->route_name ?? ''),
                'Salesman Name'   => (string) ($unload->salesman->name ?? ''),
                'Project Type'    => (string) ($unload->projecttype->salesman_type_name ?? ''),
                'Latitude'        => (string) ($unload->latitude ?? ''),
                'Longitude'       => (string) ($unload->longtitude ?? ''),
                'Unload From'     => (string) ($unload->unload_from ?? ''),
                'Load Date'       => (string) ($unload->load_date ?? ''),
                'Status'          => $unload->status == 1 ? 'Active' : 'Inactive',
            ];
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