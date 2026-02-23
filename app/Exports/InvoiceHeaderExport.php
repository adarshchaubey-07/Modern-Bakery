<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\CommonLocationFilter;

class InvoiceHeaderExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $filters;

    public function __construct($fromDate = null, $toDate = null, $filters = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->filters  = $filters;
    }

    public function query()
    {
        $query = InvoiceHeader::with([
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
        ]);

        if (!empty($this->filters)) {
            $query = CommonLocationFilter::apply($query, $this->filters);
        }

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('invoice_date', [
                \Carbon\Carbon::parse($this->fromDate)->startOfDay(),
                \Carbon\Carbon::parse($this->toDate)->endOfDay(),
            ]);
        } elseif ($this->fromDate) {
            $query->whereDate('invoice_date', '>=', $this->fromDate);
        } elseif ($this->toDate) {
            $query->whereDate('invoice_date', '<=', $this->toDate);
        }

        return $query->orderBy('invoice_date', 'desc');
    }

    public function map($header): array
    {
        return [
            $header->invoice_code,
            $header->currency_name,
            $header->order->order_code ?? '',
            $header->delivery->delivery_code ?? '',
            trim(
                ($header->route->route_code ?? '') . ' - ' .
                ($header->route->route_name ?? '')
            ),

            trim(
                ($header->customer->osa_code ?? '') . ' - ' .
                ($header->customer->name ?? '')
            ),

            trim(
                ($header->salesman->osa_code ?? '') . ' - ' .
                ($header->salesman->name ?? '')
            ),

            $header->invoice_date,
            $header->invoice_time,
            (float) $header->vat,
            (float) $header->net_total,
            (float) $header->gross_total,
            (float) $header->discount,
            (float) $header->total_amount,
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Currency Name',
            'Order Code',
            'Delivery Code',
            'Route',
            'Customer',
            'Salesman',
            'Invoice Date',
            'Invoice Time',
            'VAT',
            'Net Total',
            'Gross Total',
            'Discount',
            'Total Amount',
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
