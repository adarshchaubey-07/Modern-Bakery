<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\AgentDeliveryDetails;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DeliveryCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $groupIndexes = [];

    public function collection()
    {
        $rows = [];
        $rowIndex = 2; // because row 1 is headings

        $headers = AgentDeliveryHeaders::with(['warehouse', 'route', 'salesman', 'customer', 'country'])->get();

        foreach ($headers as $header) {
            $headerRowIndex = $rowIndex;

            $rows[] = [
                'Delivery Code'  => (string)($header->delivery_code ?? ''),
                'Warehouse'      => (string)($header->warehouse->warehouse_name ?? ''),
                'Route'          => (string)($header->route->route_name ?? ''),
                'Salesman'       => (string)($header->salesman->name ?? ''),
                'Customer'       => (string)($header->customer->name ?? ''),
                'Country'        => (string)($header->country->country_name ?? ''),
                'Currency'       => (string)($header->country->currency ?? ''),
                'Gross Total'    => (float)($header->gross_total ?? 0),
                'VAT'            => (float)($header->vat ?? 0),
                'Discount'       => (float)($header->discount ?? 0),
                'Net Amount'     => (float)($header->net_amount ?? 0),
                'Total'          => (float)($header->total ?? 0),
                'Delivery Date'  => (string)($header->delivery_date ?? ''),
                'Comment'        => (string)($header->comment ?? ''),
                'Status'         => (string)($header->status == 1 ? 'Active' : 'Inactive'),

                'Item Name'      => '',
                'UOM'            => '',
                'Item Price'     => '',
                'Quantity'       => '',
                'Item VAT'       => '',
                'Item Discount'  => '',
                'Item Gross'     => '',
                'Item Net'       => '',
                'Item Total'     => '',
                'Promotional'    => '',
            ];

            $rowIndex++;

            $details = AgentDeliveryDetails::with(['item', 'itemuom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
                    'Delivery Code'  => '',
                    'Warehouse'      => '',
                    'Route'          => '',
                    'Salesman'       => '',
                    'Customer'       => '',
                    'Country'        => '',
                    'Currency'       => '',
                    'Gross Total'    => '',
                    'VAT'            => '',
                    'Discount'       => '',
                    'Net Amount'     => '',
                    'Total'          => '',
                    'Delivery Date'  => '',
                    'Comment'        => '',
                    'Status'         => '',

                    'Item Name'      => (string)($detail->item->name ?? ''),
                    'UOM'            => (string)($detail->itemuom->name ?? ''),
                    'Item Price'     => (float)($detail->item_price ?? 0),
                    'Quantity'       => (float)($detail->quantity ?? 0),
                    'Item VAT'       => (float)($detail->vat ?? 0),
                    'Item Discount'  => (float)($detail->discount ?? 0),
                    'Item Gross'     => (float)($detail->gross_total ?? 0),
                    'Item Net'       => (float)($detail->net_total ?? 0),
                    'Item Total'     => (float)($detail->total ?? 0),
                    'Promotional'    => $detail->is_promotional ? 'Yes' : 'No',
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

            $rows[] = array_fill_keys($this->headings(), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Delivery Code',
            'Warehouse',
            'Route',
            'Salesman',
            'Customer',
            'Country',
            'Currency',
            'Gross Total',
            'VAT',
            'Discount',
            'Net Amount',
            'Total',
            'Delivery Date',
            'Comment',
            'Status',
            'Item Name',
            'UOM',
            'Item Price',
            'Quantity',
            'Item VAT',
            'Item Discount',
            'Item Gross',
            'Item Net',
            'Item Total',
            'Promotional',
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

                // Collapse detail rows
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