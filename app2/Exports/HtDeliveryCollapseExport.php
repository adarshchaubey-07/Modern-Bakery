<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use App\Models\Hariss_Transaction\Web\HTDeliveryDetail;
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

class HtDeliveryCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
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

        $query = HTDeliveryHeader::with(['customer', 'salesman', 'country', 'poorder', 'order']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('delivery_date', [$this->startDate, $this->endDate]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            $rows[] = [
                'Delivery Code'    => (string) $header->delivery_code,
                'Customer Code'    => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'    => (string) ($header->customer->name ?? ''),
                'Customer Email'   => (string) ($header->customer->email ?? ''),
                'Customer Town'    => (string) ($header->customer->town ?? ''),
                'Customer Street'  => (string) ($header->customer->street ?? ''),
                'Customer Contact' => (string) ($header->customer->contact_no ?? ''),

                'Salesman Code'    => (string) ($header->salesman->osa_code ?? ''),
                'Salesman Name'    => (string) ($header->salesman->name ?? ''),

                'Country Code'     => (string) ($header->country->country_code ?? ''),
                'Country Name'     => (string) ($header->country->country_name ?? ''),

                'Delivery Date'    => (string) ($header->delivery_date?->format('Y-m-d') ?? ''),
                'Comment'          => (string) ($header->comment ?? ''),
                'Status'           => $header->status == 1 ? 'Active' : 'Inactive',

                'Currency'         => (string) $header->currency,
                'Gross Total'      => (float) $header->gross_total,
                'Discount'         => (float) $header->discount,
                'VAT'              => (float) $header->vat,
                'Pre VAT'          => (float) $header->pre_vat,
                'Net'              => (float) $header->net,
                'Excise'           => (float) $header->excise,
                'Total'            => (float) $header->total,

                'PO Code'          => (string) ($header->poorder->order_code ?? ''),
                'Order Code'       => (string) ($header->order->order_code ?? ''),

                'Item Code'        => '',
                'Item Name'        => '',
                'UOM Name'         => '',
                'Item Price'       => '',
                'Quantity'         => '',
                'Detail Discount'  => '',
                'Detail Gross'     => '',
                'Promotion'        => '',
                'Net Detail'       => '',
                'Excise Detail'    => '',
                'Pre VAT Detail'   => '',
                'Detail VAT'       => '',
                'Detail Total'     => '',
            ];

            $rowIndex++;

            $details = HTDeliveryDetail::with(['item', 'itemuom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'Delivery Code'    => '',
                    'Customer Code'    => '',
                    'Customer Name'    => '',
                    'Customer Email'   => '',
                    'Customer Town'    => '',
                    'Customer Street'  => '',
                    'Customer Contact' => '',
                    'Salesman Code'    => '',
                    'Salesman Name'    => '',
                    'Country Code'     => '',
                    'Country Name'     => '',
                    'Delivery Date'    => '',
                    'Comment'          => '',
                    'Status'           => '',
                    'Currency'         => '',
                    'Gross Total'      => '',
                    'Discount'         => '',
                    'VAT'              => '',
                    'Pre VAT'          => '',
                    'Net'              => '',
                    'Excise'           => '',
                    'Total'            => '',
                    'PO Code'          => '',
                    'Order Code'       => '',

                    'Item Code'        => (string) ($detail->item->code ?? ''),
                    'Item Name'        => (string) ($detail->item->name ?? ''),
                    'UOM Name'         => (string) ($detail->itemuom->name ?? ''),
                    'Item Price'       => (float) $detail->item_price,
                    'Quantity'         => (float) $detail->quantity,
                    'Detail Discount'  => (float) $detail->discount,
                    'Detail Gross'     => (float) $detail->gross_total,
                    'Promotion'        => (bool) $detail->promotion,
                    'Net Detail'       => (float) $detail->net,
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
            'Delivery Code',
            'Customer Code',
            'Customer Name',
            'Customer Email',
            'Customer Town',
            'Customer Street',
            'Customer Contact',

            'Salesman Code',
            'Salesman Name',

            'Country Code',
            'Country Name',

            'Delivery Date',
            'Comment',
            'Status',

            'Currency',
            'Gross Total',
            'Discount',
            'VAT',
            'Pre VAT',
            'Net',
            'Excise',
            'Total',

            'PO Code',
            'Order Code',

            'Item Code',
            'Item Name',
            'UOM Name',
            'Item Price',
            'Quantity',
            'Detail Discount',
            'Detail Gross',
            'Promotion',
            'Net Detail',
            'Excise Detail',
            'Pre VAT Detail',
            'Detail VAT',
            'Detail Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AO1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AO1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
