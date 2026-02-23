<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtInvoiceFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $query = HTInvoiceHeader::with(['customer', 'salesman', 'company']);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('invoice_date', [$this->from_date, $this->to_date]);
        }

        $invoices = $query->get();
        $rows = [];

        foreach ($invoices as $i) {
            $rows[] = [

                'Invoice Code'      => (string) $i->invoice_code,

                'Customer Code'     => (string) ($i->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($i->customer->name ?? ''),
                'Customer Email'    => (string) ($i->customer->email ?? ''),
                'Customer Town'     => (string) ($i->customer->town ?? ''),
                'Customer Street'   => (string) ($i->customer->street ?? ''),
                'Customer Contact'  => (string) ($i->customer->contact_no ?? ''),

                'Salesman Code'     => (string) ($i->salesman->osa_code ?? ''),
                'Salesman Name'     => (string) ($i->salesman->name ?? ''),

                'Company Code'      => (string) ($i->company->company_code ?? ''),
                'Company Name'      => (string) ($i->company->company_name ?? ''),

                'Currency Name'     => (string) $i->currency_name,
                'Order Number'      => (string) $i->order_number,
                'Delivery Number'   => (string) $i->delivery_number,
                'Post Order Code'           => (string) ($i->poorder->order_code ?? ''),
                'Order Code'        => (string) ($i->order->order_code ?? ''),

                'Latitude'          => (string) $i->latitude,
                'Longitude'         => (string) $i->longitude,

                'Purchaser Name'    => (string) $i->purchaser_name,
                'Purchaser Contact' => (string) $i->purchaser_contact,

                'Invoice Date'      => $i->invoice_date ? date('Y-m-d', strtotime($i->invoice_date)) : null,
                'Invoice Time'      => (string) $i->invoice_time,

                'Net Amount'        => (float) $i->net,
                'VAT'               => (float) $i->vat,
                'Excise'            => (float) $i->excise,
                'Total'             => (float) $i->total,

                'Status'            => $i->status,
            ];
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

            'Post Order Code',
            'Order Code',

            'Latitude',
            'Longitude',

            'Purchaser Name',
            'Purchaser Contact',

            'Invoice Date',
            'Invoice Time',

            'Net Amount',
            'VAT',
            'Excise',
            'Total',

            'Status',
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
