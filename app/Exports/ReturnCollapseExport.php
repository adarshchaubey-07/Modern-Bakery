<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ReturnDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReturnCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected array $groupIndexes = [];
    public function collection()
{
    $rows = [];
    $rowIndex = 2;

    $headers = ReturnHeader::with([
        'country',
        'order',
        'delivery',
        'warehouse',
        'route',
        'customer',
        'salesman',
        'details.item',
        'details.uom',
    ])->get();

    foreach ($headers as $header) {

        $details   = $header->details;
        $itemCount = $details->count();
        $headerRow = $rowIndex;

        $rows[] = $this->emptyRow([
            'OSA Code'   => (string) $header->osa_code,
            'Order Code' => (string) ($header->order->order_code ?? ''),
            'Warehouse'  => trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
            'Route'      => trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
            'Customer'   => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
            'Salesman'   => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
            'VAT'        => (float) $header->vat,
            'Net Amount' => (float) $header->net_amount,
            'Total'      => (float) $header->total,
            'Item Count' => $itemCount, 
        ]);

        $rowIndex++;
        $detailRowIndexes = [];
        foreach ($details as $detail) {

            $rows[] = $this->emptyRow([
                'Item' => trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                'UOM'  => $detail->uom->name ?? '',
                'Item Price'    => (float) $detail->item_price,
                'Item Quantity' => (float) $detail->item_quantity,
                'VAT (Detail)'  => (float) $detail->vat,
                'Net Total (Detail)' => (float) $detail->net_total,
                'Total (Detail)'     => (float) $detail->total,
            ]);

            $detailRowIndexes[] = $rowIndex;
            $rowIndex++;
        }

        if (!empty($detailRowIndexes)) {
            $this->groupIndexes[] = [
                'start' => $headerRow + 1,
                'end'   => max($detailRowIndexes),
            ];
        }

        $rows[] = $this->emptyRow();
        $rowIndex++;
    }

    return new Collection($rows);
}
    public function headings(): array
    {
        return [
            'OSA Code',
            'Order Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Amount',
            'Total',
            'Item Count',
            'Item',
            'UOM',
            'Item Price',
            'Item Quantity',
            'VAT (Detail)',
            'Net Total (Detail)',
            'Total (Detail)',
        ];
    }
    private function emptyRow(array $data = []): array
    {
        $row = array_fill_keys($this->headings(), '');

        foreach ($data as $key => $value) {
            $row[$key] = $value;
        }

        return $row;
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
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
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
