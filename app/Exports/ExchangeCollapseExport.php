<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInReturn;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExchangeCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $groupIndexes = [];

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $headers = ExchangeHeader::with(['warehouse', 'customer'])->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;
            $rows[] = [
                'Exchange Code'     => (string) $header->exchange_code,
                'Warehouse Name'    => (string) ($header->warehouse->warehouse_name ?? ''),
                'Customer Code'     => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($header->customer->name ?? ''),
                'Comment'           => (string) $header->comment,
                'Status'            => $header->status == 1 ? 'Active' : 'Inactive',
                'Section'           => '',
                'Item Code'         => '',
                'Item Name'         => '',
                'UOM Name'          => '',
                'Item Price'        => '',
                'Item Quantity'     => '',
                'Total'             => '',
                'Return Type'       => '',
                'Region'            => '',
                'Detail Status'     => '',
            ];

            $rowIndex++;

            $detailRowIndexes = [];

            $collects = ExchangeInReturn::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            if ($collects->count() > 0) {
                $rows[] = [
                    'Exchange Code' => '',
                    'Warehouse Name' => '',
                    'Customer Code' => '',
                    'Customer Name' => '',
                    'Comment' => '',
                    'Status' => '',
                    'Section' => 'Collect',
                    'Item Code' => '',
                    'Item Name' => '',
                    'UOM Name' => '',
                    'Item Price' => '',
                    'Item Quantity' => '',
                    'Total' => '',
                    'Return Type' => '',
                    'Region' => '',
                    'Detail Status' => '',
                ];

                $rowIndex++;

                foreach ($collects as $detail) {
                    $rows[] = [
                        'Exchange Code'  => '',
                        'Warehouse Name' => '',
                        'Customer Code'  => '',
                        'Customer Name'  => '',
                        'Comment'        => '',
                        'Status'         => '',
                        'Section'        => '',
                        'Item Code'      => '→ ' . ($detail->item->code ?? ''),
                        'Item Name'      => $detail->item->name ?? '',
                        'UOM Name'       => $detail->uoms->name ?? '',
                        'Item Price'     => (float) $detail->item_price,
                        'Item Quantity'  => (float) $detail->item_quantity,
                        'Total'          => (float) $detail->total,
                        'Return Type'    => $detail->return_type ?? '',
                        'Region'         => $detail->region ?? '',
                        'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
                    ];

                    $detailRowIndexes[] = $rowIndex;
                    $rowIndex++;
                }
            }
            $returns = ExchangeInInvoice::with(['item', 'inuoms'])
                ->where('header_id', $header->id)
                ->get();

            if ($returns->count() > 0) {
                $rows[] = [
                    'Exchange Code' => '',
                    'Warehouse Name' => '',
                    'Customer Code' => '',
                    'Customer Name' => '',
                    'Comment' => '',
                    'Status' => '',
                    'Section' => 'Return',
                    'Item Code' => '',
                    'Item Name' => '',
                    'UOM Name' => '',
                    'Item Price' => '',
                    'Item Quantity' => '',
                    'Total' => '',
                    'Detail Status' => '',
                ];

                $rowIndex++;

                foreach ($returns as $detail) {
                    $rows[] = [
                        'Exchange Code'  => '',
                        'Warehouse Name' => '',
                        'Customer Code'  => '',
                        'Customer Name'  => '',
                        'Comment'        => '',
                        'Status'         => '',
                        'Section'        => '',
                        'Item Code'      => '→ ' . ($detail->item->code ?? ''),
                        'Item Name'      => $detail->item->name ?? '',
                        'UOM Name'       => $detail->inuoms->name ?? '',
                        'Item Price'     => (float) $detail->item_price,
                        'Item Quantity'  => (float) $detail->item_quantity,
                        'Total'          => (float) $detail->total,
                        // Return section does NOT include return_type or region
                        'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
                    ];

                    $detailRowIndexes[] = $rowIndex;
                    $rowIndex++;
                }
            }

            // Group collapse tracking
            if (count($detailRowIndexes) > 0) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            // Empty row after each group
            $rows[] = array_fill_keys($this->headings(), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Exchange Code',
            'Warehouse Name',
            'Customer Code',
            'Customer Name',
            'Comment',
            'Status',
            'Section',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Item Price',
            'Item Quantity',
            'Total',
            'Return Type',
            'Region',
            'Detail Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Style headings
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'F5F5F5'],
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
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                // Collapse groups
                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
