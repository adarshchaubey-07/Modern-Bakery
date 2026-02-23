<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtOrderFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date = null, $to_date = null)
    {
        $this->from_date = $from_date;
        $this->to_date   = $to_date;
    }

    public function collection()
    {
        $query = HTOrderHeader::with(['customer', 'salesman', 'country']);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('delivery_date', [$this->from_date, $this->to_date]);
        }

        $orders = $query->get();

        $rows = [];

        foreach ($orders as $h) {
            $rows[] = [

                'Order Code'        => (string) $h->order_code,

                'Customer ID'       => $h->customer_id,
                'Customer Code'     => (string) ($h->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($h->customer->name ?? ''),
                'Customer Email'    => (string) ($h->customer->email ?? ''),
                'Customer Town'     => (string) ($h->customer->town ?? ''),
                'Customer Street'   => (string) ($h->customer->street ?? ''),
                'Customer Contact'  => (string) ($h->customer->contact_no ?? ''),

                'Salesman ID'       => $h->salesman_id,
                'Salesman Code'     => (string) ($h->salesman->osa_code ?? ''),
                'Salesman Name'     => (string) ($h->salesman->name ?? ''),

                'Country ID'        => $h->country_id,
                'Country Code'      => (string) ($h->country->country_code ?? ''),
                'Country Name'      => (string) ($h->country->country_name ?? ''),

                'Delivery Date'     => $h->delivery_date?->format('Y-m-d'),
                'Order Date'        => $h->order_date?->format('Y-m-d'),
                'Comment'           => (string) $h->comment,
                'Status'            => $h->status,
                'Currency'          => (string) $h->currency,

                'Gross Total'       => (float) $h->gross_total,
                'Pre VAT'           => (float) $h->pre_vat,
                'Discount'          => (float) $h->discount,
                'Net Amount'        => (float) $h->net_amount,
                'Total'             => (float) $h->total,
                'Excise'            => (float) $h->excise,
                'VAT'               => (float) $h->vat,

                'SAP ID'            => (string) $h->sap_id,
                'SAP Message'       => (string) $h->sap_msg,
                'Document Type'     => (string) $h->doc_type,

                'PO ID'             => $h->po_id,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Order Code',

            'Customer ID',
            'Customer Code',
            'Customer Name',
            'Customer Email',
            'Customer Town',
            'Customer Street',
            'Customer Contact',

            'Salesman ID',
            'Salesman Code',
            'Salesman Name',

            'Country ID',
            'Country Code',
            'Country Name',

            'Delivery Date',
            'Order Date',
            'Comment',
            'Status',
            'Currency',

            'Gross Total',
            'Pre VAT',
            'Discount',
            'Net Amount',
            'Total',
            'Excise',
            'VAT',

            'SAP ID',
            'SAP Message',
            'Document Type',

            'PO ID',
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
            },
        ];
    }
}
