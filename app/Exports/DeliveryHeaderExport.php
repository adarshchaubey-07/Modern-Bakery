<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
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

class DeliveryHeaderExport implements
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
        $query = AgentDeliveryHeaders::with([
            'warehouse',
            'route',
            'salesman',
            'customer',
            'country',
        ]);

        $query = CommonLocationFilter::apply($query, $this->filters);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59',
            ]);
        }

        $headers = $query
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];

        foreach ($headers as $header) {

            $rows[] = [
                (string) ($header->delivery_code ?? ''),
                optional($header->created_at)->format('Y-m-d'),
                $header->order_code ?? '',
                trim(
                    ($header->route->route_code ?? '') . ' - ' .
                    ($header->route->route_name ?? '')
                ),
                trim(
                    ($header->salesman->osa_code ?? '') . ' - ' .
                    ($header->salesman->name ?? '')
                ),
                trim(
                    ($header->customer->osa_code ?? '') . ' - ' .
                    ($header->customer->name ?? '')
                ),
                (float) ($header->vat ?? 0),
                (float) ($header->discount ?? 0),
                (float) ($header->net_amount ?? 0),
                (float) ($header->gross_total ?? 0),
                (float) ($header->total ?? 0),
                $header->comment ?? '',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Delivery Code',
            'Delivery Date',
            'Order Code',
            'Route',
            'Salesman',
            'Customer',
            'VAT',
            'Discount',
            'Net Amount',
            'Gross Total',
            'Total',
            'Comment',
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
