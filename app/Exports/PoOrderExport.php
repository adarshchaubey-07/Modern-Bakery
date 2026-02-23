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
            $query->whereBetween('order_date', [$this->from_date, $this->to_date]);
        }

        $headers = $query->get();
        $rows = [];

        foreach ($headers as $h) {
            $rows[] = [
                'Order Code'       => (string) $h->order_code,
                'Order Date'       => (string) ($h->order_date?->format('Y-m-d') ?? ''),
                'Delivery Date'    => (string) ($h->delivery_date?->format('Y-m-d') ?? ''),
                'SAP ID'           => (string) $h->sap_id,
                'SAP MSG'          => (string) $h->sap_msg,
                'Customer'         => trim(($h->customer->osa_code ?? '') . '-' . ($h->customer->business_name ?? '')),
                'Salesman'         => trim(($h->salesman->osa_code ?? '') . '-' . ($h->salesman->name ?? '')),
                'Comment'          => (string) $h->comment,
                'Net Amount'       => (float) $h->net_amount,
                'VAT'              => (float) $h->vat,
                'Total'            => (float) $h->total,
                'Status'           => $h->status == 1 ? 'Active' : 'Inactive',

            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
                    'Order Code',
                    'Order Date',
                    'Delivery Date',
                    'SAP ID',
                    'SAP MSG',
                    'Customer',
                    'Salesman',
                    'Comment',
                    'Net Amount',
                    'VAT',
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
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
