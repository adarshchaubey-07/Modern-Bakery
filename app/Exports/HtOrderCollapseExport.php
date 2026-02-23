<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use App\Models\Hariss_Transaction\Web\HTOrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtOrderCollapseExport implements FromCollection,WithHeadings,ShouldAutoSize,WithEvents,WithStyles
{
    protected $groupIndexes = [];
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = HTOrderHeader::with(['customer', 'salesman']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('order_date', [$this->startDate, $this->endDate]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;
            $rows[] = [
                'Order Code'        => (string) $header->order_code,
                'Order Date'        => (string) ($header->order_date?->format('Y-m-d') ?? ''),
                'Customer'          => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Salesman'          => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                'Delivery Date'     => (string) ($header->delivery_date?->format('Y-m-d') ?? ''),
                'SAP ID'            => (string) $header->sap_id,
                'SAP MSG'           => (string) $header->sap_msg,
                'Comment'           => (string) ($header->comment ?? ''),
                'Net Amount'        => (float) $header->net_amount,
                'Excise'            => (float) $header->excise,
                'VAT'               => (float) $header->vat,
                'Total'             => (float) $header->total,

                'Item'              => '',
                'UOM Name'          => '',
                'Item Price'        => '',
                'Quantity'          => '',
                'Net'               => '',
                'Excise Detail'     => '',
                'Detail VAT'        => '',
                'Detail Total'      => '',
            ];

            $rowIndex++;

            $details = HTOrderDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
                    'Order Code'        => '',
                    'Order Date'        => '',
                    'Customer'          => '',
                    'Salesman'          => '',
                    'Delivery Date'     => '',
                    'SAP ID'            => '',
                    'SAP MSG'           => '',
                    'Comment'           => '',
                    'Net Amount'        => '',
                    'Excise'            => '',
                    'VAT'               => '',
                    'Total'             => '',
              
                    'Item'              => trim(($header->item->erp_code ?? '') . ' - ' . ($header->item->name ?? '')),
                    'UOM Name'          => (string) ($detail->uoms->name ?? ''),
                    'Item Price'        => (float) $detail->item_price,
                    'Quantity'          => (float) $detail->quantity,
                    'Net'               => (float) $detail->net,
                    'Excise Detail'     => (float) $detail->excise,
                    'Detail VAT'        => (float) $detail->vat,
                    'Detail Total'      => (float) $detail->total,
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

            $rows[] = array_fill_keys(array_keys($rows[0]), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
                    'Order Code',
                    'Order Date',
                    'Customer',
                    'Salesman',
                    'Delivery Date',
                    'SAP ID',
                    'SAP MSG',
                    'Comment',
                    'Net Amount',
                    'Excise',
                    'VAT',
                    'Total',

                'Item',
                'UOM Name',
                'Item Price',
                'Quantity',
                'Net',
                'Excise Detail',
                'Detail VAT',
                'Detail Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
                        ],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
