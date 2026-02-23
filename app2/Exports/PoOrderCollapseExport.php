<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
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

class PoOrderCollapseExport implements 
    FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithEvents, 
    WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate = null, $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = PoOrderHeader::with(['customer', 'salesman']);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);
        }   

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            $rows[] = [
                'Order Code'       => (string) $header->order_code,

                'Customer Code'    => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'    => (string) ($header->customer->business_name ?? ''),
                'Customer Town'    => (string) ($header->customer->town ?? ''),
                'Customer District'=> (string) ($header->customer->district ?? ''),
                'Customer Contact' => (string) ($header->customer->contact_number ?? ''),

                'Salesman Code'    => (string) ($header->salesman->osa_code ?? ''),
                'Salesman Name'    => (string) ($header->salesman->name ?? ''),

                'Delivery Date'    => (string) $header->delivery_date,
                'Comment'          => (string) $header->comment,
                'Status'           => $header->status == 1 ? 'Active' : 'Inactive',

                'Gross Total'      => (float) $header->gross_total,
                'Pre VAT'          => (float) $header->pre_vat,
                'Discount'         => (float) $header->discount,
                'Net Amount'       => (float) $header->net_amount,
                'Total'            => (float) $header->total,
                'Excise'           => (float) $header->excise,
                'VAT'              => (float) $header->vat,

                'SAP ID'           => (string) $header->sap_id,
                'SAP MSG'          => (string) $header->sap_msg,

                'Item Code'        => '',
                'Item Name'        => '',
                'UOM Name'         => '',
                'Item Price'       => '',
                'Quantity'         => '',
                'Detail Discount'  => '',
                'Detail Gross'     => '',
                'Promotion'        => '',
                'Net'              => '',
                'Excise Detail'    => '',
                'Pre VAT Detail'   => '',
                'Detail VAT'       => '',
                'Detail Total'     => '',
            ];

            $rowIndex++;

            $details = POOrderDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [

                    'Order Code'       => '',
                    'Customer Code'    => '',
                    'Customer Name'    => '',
                    'Customer Town'    => '',
                    'Customer District'=> '',
                    'Customer Contact' => '',
                    'Salesman Code'    => '',
                    'Salesman Name'    => '',
                    'Delivery Date'    => '',
                    'Comment'          => '',
                    'Status'           => '',
                    'Gross Total'      => '',
                    'Pre VAT'          => '',
                    'Discount'         => '',
                    'Net Amount'       => '',
                    'Total'            => '',
                    'Excise'           => '',
                    'VAT'              => '',
                    'SAP ID'           => '',
                    'SAP MSG'          => '',

                    'Item Code'        => (string) ($detail->item->code ?? ''),
                    'Item Name'        => (string) ($detail->item->name ?? ''),
                    'UOM Name'         => (string) ($detail->uom->name ?? ''),
                    'Item Price'       => (float) $detail->item_price,
                    'Quantity'         => (float) $detail->quantity,
                    'Detail Discount'  => (float) $detail->discount,
                    'Detail Gross'     => (float) $detail->gross_total,
                    'Promotion'        => (bool) $detail->promotion,
                    'Net'              => (float) $detail->net,
                    'Excise Detail'    => (float) $detail->excise,
                    'Pre VAT Detail'   => (float) $detail->pre_vat,
                    'Detail VAT'       => (float) $detail->vat,
                    'Detail Total'     => (float) $detail->total,
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
            'Customer Code',
            'Customer Name',
            'Customer Town',
            'Customer District',
            'Customer Contact',
            'Salesman Code',
            'Salesman Name',
            'Delivery Date',
            'Comment',
            'Status',
            'Gross Total',
            'Pre VAT',
            'Discount',
            'Net Amount',
            'Total',
            'Excise',
            'VAT',
            'SAP ID',
            'SAP MSG',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Item Price',
            'Quantity',
            'Detail Discount',
            'Detail Gross',
            'Promotion',
            'Net',
            'Excise Detail',
            'Pre VAT Detail',
            'Detail VAT',
            'Detail Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AH1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AH1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
