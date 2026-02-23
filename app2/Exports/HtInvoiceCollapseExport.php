<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
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

class HtInvoiceCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
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

        $query = HTInvoiceHeader::with([
            'customer',
            'salesman',
            'company',
            'delivery',
            'poorder',
            'order'
        ]);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('invoice_date', [$this->startDate, $this->endDate]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            $rows[] = [
                'Invoice Code'      => (string) $header->invoice_code,
                'Customer Code'     => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($header->customer->name ?? ''),
                'Customer Email'    => (string) ($header->customer->email ?? ''),
                'Customer Town'     => (string) ($header->customer->town ?? ''),
                'Customer Street'   => (string) ($header->customer->street ?? ''),
                'Customer Contact'  => (string) ($header->customer->contact_no ?? ''),

                'Salesman Code'     => (string) ($header->salesman->osa_code ?? ''),
                'Salesman Name'     => (string) ($header->salesman->name ?? ''),

                'Company Code'      => (string) ($header->company->company_code ?? ''),
                'Company Name'      => (string) ($header->company->company_name ?? ''),

                'Currency Name'     => (string) $header->currency_name,
                'Order Number'      => (string) $header->order_number,
                'Delivery Number'   => (string) $header->delivery_number,

                'Latitude'          => (string) $header->latitude,
                'Longitude'         => (string) $header->longitude,
                'Purchaser Name'    => (string) $header->purchaser_name,
                'Purchaser Contact' => (string) $header->purchaser_contact,

                'Invoice Date'      => (string) ($header->invoice_date ?? ''),
                'Invoice Time'      => (string) $header->invoice_time,

                'Net'               => (float) $header->net,
                'VAT'               => (float) $header->vat,
                'Excise'            => (float) $header->excise,
                'Total'             => (float) $header->total,

                'Delivery Code'     => (string) ($header->delivery->delivery_code ?? ''),
                'PO Code'           => (string) ($header->poorder->order_code ?? ''),
                'Order Code'        => (string) ($header->order->order_code ?? ''),

                'Status'            => $header->status == 1 ? 'Active' : 'Inactive',

                'Item ID'           => '',
                'Item Code'         => '',
                'Item Name'         => '',
                'UOM Name'          => '',
                'Quantity'          => '',
                'Item Price'        => '',
                'Discount'          => '',
                'Gross Total'       => '',
                'Net Detail'        => '',
                'VAT Detail'        => '',
                'Total Detail'      => '',
                'Batch Number'      => '',
            ];

            $rowIndex++;
            $details = HTInvoiceDetail::with(['item', 'itemuom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'Invoice Code'      => '',
                    'Customer Code'     => '',
                    'Customer Name'     => '',
                    'Customer Email'    => '',
                    'Customer Town'     => '',
                    'Customer Street'   => '',
                    'Customer Contact'  => '',

                    'Salesman Code'     => '',
                    'Salesman Name'     => '',
                    'Company Code'      => '',
                    'Company Name'      => '',

                    'Currency Name'     => '',
                    'Order Number'      => '',
                    'Delivery Number'   => '',
                    'Latitude'          => '',
                    'Longitude'         => '',
                    'Purchaser Name'    => '',
                    'Purchaser Contact' => '',
                    'Invoice Date'      => '',
                    'Invoice Time'      => '',
                    'Net'               => '',
                    'VAT'               => '',
                    'Excise'            => '',
                    'Total'             => '',
                    'Delivery Code'     => '',
                    'PO Code'           => '',
                    'Order Code'        => '',
                    'Status'            => '',

                    'Item ID'           => (int) $detail->item_id,
                    'Item Code'         => (string) ($detail->item->code ?? ''),
                    'Item Name'         => (string) ($detail->item->name ?? ''),
                    'UOM Name'          => (string) ($detail->itemuom->name ?? ''),
                    'Quantity'          => (float) $detail->quantity,
                    'Item Price'        => (float) $detail->item_price,
                    'Discount'          => (float) $detail->discount,
                    'Gross Total'       => (float) $detail->gross_total,
                    'Net Detail'        => (float) $detail->net,
                    'VAT Detail'        => (float) $detail->vat,
                    'Total Detail'      => (float) $detail->total,
                    'Batch Number'      => (string) $detail->batch_number,
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
            'Invoice Code',
            'Customer Code',
            'Customer Name',
            'Customer Email',
            'Customer Town',
            'Customer Street',
            'Customer Contact',

            'Salesman Code',
            'Salesman Name',

            'Company Code',
            'Company Name',

            'Currency Name',
            'Order Number',
            'Delivery Number',
            'Latitude',
            'Longitude',
            'Purchaser Name',
            'Purchaser Contact',

            'Invoice Date',
            'Invoice Time',

            'Net',
            'VAT',
            'Excise',
            'Total',

            'Delivery Code',
            'PO Code',
            'Order Code',
            'Status',

            'Item ID',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Item Price',
            'Discount',
            'Gross Total',
            'Net Detail',
            'VAT Detail',
            'Total Detail',
            'Batch Number',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AZ1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AZ1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
