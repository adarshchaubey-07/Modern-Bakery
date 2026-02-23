<?php
namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class InvoiceCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $from;
    protected $to;
    protected $groups = [];

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2; // row 1 = headings

        $headers = InvoiceHeader::with([
            'company',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman'
        ])
            ->when($this->from, fn($q) => $q->whereDate('invoice_date', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('invoice_date', '<=', $this->to))
            ->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            // HEADER ROW
            $rows[] = [
                $header->invoice_code,
                $header->company->currency_name ?? '',
                $header->company->name ?? '',
                $header->order->order_code ?? '',
                $header->delivery->delivery_code ?? '',
                $header->warehouse->warehouse_code ?? '',
                $header->warehouse->warehouse_name ?? '',
                $header->route->route_code ?? '',
                $header->route->route_name ?? '',
                $header->customer->osa_code ?? '',
                $header->customer->name ?? '',
                $header->salesman->osa_code ?? '',
                $header->salesman->name ?? '',
                $header->invoice_date,
                $header->invoice_time,
                $header->gross_total,
                $header->vat,
                $header->pre_vat,
                $header->net_total,
                $header->promotion_total,
                $header->discount,
                $header->total_amount,
                $header->status == 1 ? 'Active' : 'Inactive',
                '', '', '', '', '', '', '', '', '', '', ''
            ];

            $rowIndex++;

            // DETAILS
            $details = InvoiceDetail::with(['item', 'itemuom', 'promotion'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $d) {
                $rows[] = [
                    '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                    '', '', '', '',
                    $d->item->item_code ?? '',
                    $d->item->name ?? '',
                    $d->itemuom->name ?? '',
                    (string) $d->quantity,
                    (string) $d->item_value,
                    (string) $d->vat,
                    (string) $d->pre_vat,
                    (string) $d->net_total,
                    (string) $d->item_total,
                    $d->promotion->code ?? '',
                    $d->status == 1 ? 'Active' : 'Inactive',
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }

            if (count($detailRowIndexes) > 0) {
                $this->groups[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Currency Name',
            'Company Name',
            'Order Code',
            'Delivery Code',
            'Warehouse Code',
            'Warehouse Name',
            'Route Code',
            'Route Name',
            'Customer Code',
            'Customer Name',
            'Salesman Code',
            'Salesman Name',
            'Invoice Date',
            'Invoice Time',
            'Gross Total',
            'VAT',
            'Pre VAT',
            'Net Total',
            'Promotion Total',
            'Discount',
            'Total Amount',
            'Status',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Item Value',
            'VAT (Detail)',
            'Pre VAT (Detail)',
            'Net Total (Detail)',
            'Item Total',
            'Promotion Code',
            'Detail Status'
        ];
    }

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $e) {

            $sheet = $e->sheet->getDelegate();

            // Summary rows above the grouped rows
            $sheet->setShowSummaryBelow(false);
            $sheet->setShowSummaryRight(false);

            foreach ($this->groups as $g) {

                $start = $g['start'];   // header row
                $end   = $g['end'];     // last detail row

                // Set outline level (grouping)
                for ($i = $start + 1; $i <= $end; $i++) {
                    $sheet->getRowDimension($i)->setOutlineLevel(1);
                    $sheet->getRowDimension($i)->setVisible(false); // hide details by default
                }

                // Very important: Collapse the group
                $sheet->getRowDimension($start)->setCollapsed(true);
            }
        }
    ];
}

}
