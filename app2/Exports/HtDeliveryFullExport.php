<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtDeliveryFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $query = HTDeliveryHeader::with(['customer', 'country', 'salesman', 'poorder', 'order']);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('delivery_date', [$this->from_date, $this->to_date]);
        }

        $deliveries = $query->get();
        $rows = [];

        foreach ($deliveries as $d) {
            $rows[] = [
                'Delivery Code'     => (string) $d->delivery_code,

                'Customer Code'     => (string) ($d->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($d->customer->name ?? ''),
                'Customer Email'    => (string) ($d->customer->email ?? ''),
                'Customer Town'     => (string) ($d->customer->town ?? ''),
                'Customer Street'   => (string) ($d->customer->street ?? ''),
                'Customer Contact'  => (string) ($d->customer->contact_no ?? ''),

                'Currency'          => (string) $d->currency,

                'Country Code'      => (string) ($d->country->country_code ?? ''),
                'Country Name'      => (string) ($d->country->country_name ?? ''),

                'Salesman Code'     => (string) ($d->salesman->osa_code ?? ''),
                'Salesman Name'     => (string) ($d->salesman->name ?? ''),

                'Gross Total'       => (float) $d->gross_total,
                'Discount'          => (float) $d->discount,
                'VAT'               => (float) $d->vat,
                'Pre VAT'           => (float) $d->pre_vat,
                'Net Amount'        => (float) $d->net,
                'Excise'            => (float) $d->excise,
                'Total'             => (float) $d->total,

                'Delivery Date'     => $d->delivery_date ? date('Y-m-d', strtotime($d->delivery_date)) : null,
                'Comment'           => (string) $d->comment,
                'Status'            => $d->status,

                'PO Code'           => (string) ($d->poorder->order_code ?? ''),
                'Order Code'        => (string) ($d->order->order_code ?? ''),
            ];
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

            'Currency',

            'Country Code',
            'Country Name',
            'Salesman Code',
            'Salesman Name',

            'Gross Total',
            'Discount',
            'VAT',
            'Pre VAT',
            'Net Amount',
            'Excise',
            'Total',

            'Delivery Date',
            'Comment',
            'Status',

            'PO Code',
            'Order Code',
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
