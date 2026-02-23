<?php

// namespace App\Exports;

// use App\Models\Agent_Transaction\OrderHeader;
// use App\Models\Agent_Transaction\OrderDetail;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;

// class OrderCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
// {
//     protected $groupIndexes = [];
//     protected $fromDate;
//     protected $toDate;

//     public function __construct($fromDate = null, $toDate = null)
//     {
//         $this->fromDate = $fromDate;
//         $this->toDate   = $toDate;
//     }
// public function collection()
// {
//     $rows = [];
//     $rowIndex = 2;

//     $statusMap = [
//         1 => 'Order Created',
//         2 => 'Delivery Created',
//         3 => 'Completed',
//     ];

// $headers = OrderHeader::with([
//         'warehouse',
//         'customer',
//         'salesman',
//         'route',
//         'details.item',
//         'details.uoms',
//     ])
//     // ->when($this->fromDate && $this->toDate, function ($q) {
//     //     $q->whereBetween('created_at', [
//     //         $this->fromDate . ' 00:00:00',
//     //         $this->toDate . ' 23:59:59',
//     //     ]);
//     // })
//     // ->when(!$this->fromDate && !$this->toDate, function ($q) {
//     //     $q->whereDate('created_at', now()->toDateString());
//     // })
//     // ->orderBy('created_at', 'desc')
//     // ->get();

//     ->when($this->fromDate && $this->toDate, function ($q) {
//     $q->whereBetween('created_at', [
//         $this->fromDate . ' 00:00:00',
//         $this->toDate . ' 23:59:59',
//     ]);
//     })
//     ->orderBy('created_at', 'desc')
//     ->get();
//     foreach ($headers as $header) {

//         $details   = $header->details;
//         $itemCount = $details->count(); 
//         $headerRow = $rowIndex;
//         $rows[] = [
//             $header->order_code,
//             optional($header->created_at)->format('Y-m-d'),
//             trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
//             trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
//             trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
//             trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
//             optional($header->delivery_date)->format('Y-m-d'),
//             $header->comment ?? '',
//             (float) $header->vat,
//             (float) $header->net_amount,
//             (float) $header->total,
//             $statusMap[$header->order_flag] ?? '-',
//             $itemCount, 
//             '', '', '', '', '', '',
//         ];

//         $rowIndex++;
//         $detailRowIndexes = [];
//         foreach ($details as $detail) {
//             $rows[] = [
//                 '', '', '', '', '', '', '', '', '', '', '', '',
//                 '',
//                 trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
//                 $detail->uoms->name ?? '',
//                 (float) $detail->item_price,
//                 (float) $detail->quantity,
//                 (float) $detail->vat,
//                 (float) $detail->net_total,
//                 (float) $detail->total,
//             ];

//             $detailRowIndexes[] = $rowIndex;
//             $rowIndex++;
//         }
//         if (!empty($detailRowIndexes)) {
//             $this->groupIndexes[] = [
//                 'start' => $headerRow + 1,
//                 'end'   => max($detailRowIndexes),
//             ];
//         }

//         $rows[] = array_fill(0, count($rows[0]), '');
//         $rowIndex++;
//     }

//     return new Collection($rows);
// }

//     public function headings(): array
//     {
//         return [
//             'Order Code',
//             'Order Date',
//             'Warehouse',
//             'Customer',
//             'Salesman',
//             'Route',
//             'Delivery Date',
//             'Comment',
//             'Vat',
//             'Net',
//             'Total',
//             'Status',
//             'Item Count',
//             'Item',
//             'UOM',
//             'Item Price',
//             'Quantity',
//             'VAT',
//             'Net Total',
//             'Total Item',
//         ];
//     }

//     public function styles(Worksheet $sheet)
//     {
//         $sheet->getStyle('A1:T1')->getFont()->setBold(true);
//         $sheet->getStyle('A1:T1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function (AfterSheet $event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => [
//                         'bold' => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                         ],
//                     ],
//                 ]);

//                 foreach ($this->groupIndexes as $group) {
//                     for ($i = $group['start']; $i <= $group['end']; $i++) {
//                         $sheet->getRowDimension($i)->setOutlineLevel(1);
//                         $sheet->getRowDimension($i)->setVisible(false);
//                     }
//                 }

//                 $sheet->setShowSummaryBelow(false);
//             },
//         ];
//     }
// }


namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
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
use App\Helpers\CommonLocationFilter;

class OrderCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $filters;

    private const COLUMN_COUNT = 18;

    public function __construct($fromDate = null, $toDate = null, $filters = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->filters  = $filters;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $statusMap = [
            1 => 'Order Created',
            2 => 'Delivery Created',
            3 => 'Completed',
        ];

        $query = OrderHeader::with([
                'customer',
                'salesman',
                'route',
                'details.item',
                'details.uoms',
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

            foreach ($headers as $header) {

            $headerRow = $rowIndex;

            $rows[] = [
                $header->order_code,
                optional($header->created_at)->format('Y-m-d'),
                optional($header->delivery_date)->format('Y-m-d'),
                $header->delivery_time ? substr($header->delivery_time, 0, 5) : '',
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                $header->currency ?? '',
                $header->comment ?? '',
                $header->customer_lpo ?? '',
                $header->division ?? '',
                (float) ($header->discount ?? 0),
                (float) ($header->vat ?? 0),
                (float) ($header->net_amount ?? 0),
                (float) ($header->gross_total ?? 0),
                (float) ($header->total ?? 0),
                $statusMap[$header->status] ?? '-',
                $header->details->count(),
            ];

            $rowIndex++;

            $rows[] = array_pad([
                '',
                'Item',
                'UOM',
                'Quantity',
                'Item Price',
                'Vat',
                'Net',
                'Gross Total',
                'Discount',
                'Total',
            ], self::COLUMN_COUNT, '');

            $detailHeadingRow = $rowIndex;
            $rowIndex++;

            foreach ($header->details as $detail) {

                $rows[] = array_pad([
                    '',
                    trim(($detail->item->code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->uoms->name ?? '',
                    (float) ($detail->quantity ?? 0),
                    (float) ($detail->item_price ?? 0),
                    (float) ($detail->vat ?? 0),
                    (float) ($detail->net_total ?? 0),
                    (float) ($detail->gross_total ?? 0),
                    (float) ($detail->discount ?? 0),
                    (float) ($detail->total ?? 0),
                ], self::COLUMN_COUNT, '');

                $rowIndex++;
            }

            if ($detailHeadingRow + 1 < $rowIndex) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start'      => $detailHeadingRow,
                    'end'        => $rowIndex - 1,
                ];
            }

            $rows[] = array_fill(0, self::COLUMN_COUNT, '');
            $rowIndex++;
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
            'Currency',
            'Comment',
            'Customer LPO',
            'Division',
            'Discount',
            'Vat',
            'Net',
            'Gross Total',
            'Total',
            'Status',
            'Item Count',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);
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
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {

                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                              ->setOutlineLevel(1)
                              ->setVisible(false);
                    }

                    $sheet->getRowDimension($group['header_row'])
                          ->setOutlineLevel(0)
                          ->setVisible(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
