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

class DeliveryCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
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

        $query = AgentDeliveryHeaders::with([
            'warehouse',
            'route',
            'salesman',
            'customer',
            'details.item',
            'details.Uom',
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
                $header->delivery_code ?? '',
                optional($header->created_at)->format('Y-m-d'),
                $header->order_code ?? '',
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                (float) ($header->vat ?? 0),
                (float) ($header->discount ?? 0),
                (float) ($header->net_amount ?? 0),
                (float) ($header->gross_total ?? 0),
                (float) ($header->total ?? 0),
                $header->comment ?? '',
                $header->details->count(),
            ];

            $rows[count($rows) - 1] = array_pad(
                $rows[count($rows) - 1],
                self::COLUMN_COUNT,
                ''
            );

            $rowIndex++;
            $rows[] = array_pad([
                '',
                'Item',
                'UOM',
                'Quantity',
                'Item Price',
                'VAT',
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
                    $detail->Uom->name ?? '',
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
            'Item Count',
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
                        ],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
