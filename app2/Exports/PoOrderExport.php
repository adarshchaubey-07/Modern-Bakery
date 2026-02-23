<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PoOrderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date = null, $to_date = null)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    public function collection()
    {
        $query = PoOrderHeader::with(['customer', 'salesman']);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('delivery_date', [$this->from_date, $this->to_date]);
        }

        $headers = $query->get();
        $rows = [];

        foreach ($headers as $h) {
            $rows[] = [
                'Order Code'        => (string) $h->order_code,

                'Customer ID'       => $h->customer_id,
                'Customer Code'     => (string) ($h->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($h->customer->business_name ?? ''),
                'Customer Town'     => (string) ($h->customer->town ?? ''),
                'Customer District' => (string) ($h->customer->district ?? ''),
                'Customer Contact'  => (string) ($h->customer->contact_number ?? ''),

                'Salesman ID'       => $h->salesman_id,
                'Salesman Code'     => (string) ($h->salesman->osa_code ?? ''),
                'Salesman Name'     => (string) ($h->salesman->name ?? ''),

                'Delivery Date'     => (string) $h->delivery_date,
                'Comment'           => (string) $h->comment,
                'Status'            => $h->status == 1 ? 'Active' : 'Inactive',

                'Gross Total'       => (float) $h->gross_total,
                'Pre VAT'           => (float) $h->pre_vat,
                'Discount'          => (float) $h->discount,
                'Net Amount'        => (float) $h->net_amount,
                'Total'             => (float) $h->total,
                'Excise'            => (float) $h->excise,
                'VAT'               => (float) $h->vat,

                'SAP ID'            => (string) $h->sap_id,
                'SAP Message'       => (string) $h->sap_msg,
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
            'Customer Town',
            'Customer District',
            'Customer Contact',

            'Salesman ID',
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
            'SAP Message',
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
