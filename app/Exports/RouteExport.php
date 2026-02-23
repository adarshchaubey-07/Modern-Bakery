<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RouteExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $routes;
    protected $columns;

    protected $map = [
        'route_code' => 'Route',
        'region'     => 'Region',
        'route_type' => 'Route Type',
        'vehicle'    => 'Vehicle',
        'status'     => 'Status',
    ];

    public function __construct($routes, $columns = [])
    {
        $this->routes  = $routes;
        $this->columns = $columns ?: array_keys($this->map);
    }

    public function collection()
    {
        return $this->routes->map(function ($route) {
            $row = [];

            foreach ($this->columns as $column) {
                $row[] = match ($column) {
                    'route_code' => trim(
                        ($route->route_code ?? '') . ' - ' .
                        ($route->route_name ?? '')
                    ),
                    'region' => trim(
                        ($route->region->region_code ?? '') . ' - ' .
                        ($route->region->region_name ?? '')
                    ),
                    'route_type' => optional($route->getrouteType)->route_type_name,
                    'vehicle'    => optional($route->vehicle)->vehicle_code,
                    'status'     => $route->status == 1 ? 'Active' : 'Inactive',
                    default      => '',
                };
            }

            return $row;
        });
    }

    public function headings(): array
    {
        return array_map(fn ($col) => $this->map[$col], $this->columns);
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
