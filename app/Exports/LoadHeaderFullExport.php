<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
use App\Helpers\CommonLocationFilter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class LoadHeaderFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $filters;

    public function __construct($fromDate = null, $toDate = null, $filters = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->filters  = $filters;
    }

    public function collection()
    {
        $query = LoadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'salesmantype',
            'projecttype'
        ]);

        $query = CommonLocationFilter::apply($query, $this->filters);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59',
            ]);
        }

        $loads = $query
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];

        foreach ($loads as $load) {

            $rows[] = [
                (string) ($load->osa_code ?? ''),
                $load->delivery_no ?? '',
                optional($load->created_at)->format('d-m-Y'),
                $load->accept_time
                    ? Carbon::parse($load->accept_time)->format('d-m-Y')
                    : '',
                trim(
                    ($load->warehouse->warehouse_code ?? '') . ' - ' .
                    ($load->warehouse->warehouse_name ?? '')
                ),
                trim(
                    ($load->route->route_code ?? '') . ' - ' .
                    ($load->route->route_name ?? '')
                ),
                trim(
                    ($load->salesman->osa_code ?? '') . ' - ' .
                    ($load->salesman->name ?? '')
                ),
                (string) ($load->salesmantype->salesman_type_name ?? ''),
                (string) ($load->projecttype->name ?? ''),
                $load->is_confirmed == 1
                    ? 'SalesTeam Accepted'
                    : 'Waiting For Accept',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Load No',
            'Delivery No',
            'Load Date',
            'Accept Date',
            'Warehouse',
            'Route',
            'Salesman',
            'Salesman Type',
            'Project Type',
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
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
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
