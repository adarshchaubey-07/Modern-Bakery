<?php

namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
use App\Helpers\CommonLocationFilter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderHeaderFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
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
        $rows = [];

        $statusMap = [
            1 => 'Order Created',
            2 => 'Delivery Created',
            3 => 'Completed',
        ];

        $query = OrderHeader::with([
            'warehouse',
            'customer',
            'salesman',
            'route'
        ]);

        $query = CommonLocationFilter::apply($query, $this->filters);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59',
            ]);
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($orders as $order) {

            $rows[] = [
                (string) $order->order_code,
                optional($order->created_at)->format('Y-m-d'),
                optional($order->delivery_date)->format('Y-m-d'),
                $order->delivery_time ? substr($order->delivery_time, 0, 5) : '',
                trim(
                    ($order->customer->osa_code ?? '') . ' - ' .
                    ($order->customer->name ?? '')
                ),
                trim(
                    ($order->salesman->osa_code ?? '') . ' - ' .
                    ($order->salesman->name ?? '')
                ),
                trim(
                    ($order->route->route_code ?? '') . ' - ' .
                    ($order->route->route_name ?? '')
                ),
                (string) ($order->comment ?? ''),
                $order->currency ?? '',
                $order->customer_lpo ?? '',
                (float) ($order->discount ?? 0),
                (float) ($order->vat ?? 0),
                (float) ($order->net_amount ?? 0),
                (float) ($order->gross_total ?? 0),
                (float) ($order->total ?? 0),
                $statusMap[$order->status] ?? 'Unknown',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Order Date',
            'Delivery Date',
            'Delivery Time',
            'Customer',
            'Salesman',
            'Route',
            'Comment',
            'Currency',
            'Customer LPO',
            'Discount',
            'Vat',
            'Net',
            'Gross Total',
            'Total',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
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
 