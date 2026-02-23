<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class InvoiceHeaderExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $fromDate, $toDate;

    public function __construct($fromDate, $toDate) {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    public function query()
    {
        $query = InvoiceHeader::with([
            'order','delivery','warehouse','route','customer','salesman'
        ]);

        if ($this->fromDate) {
            $query->whereDate('invoice_date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('invoice_date', '<=', $this->toDate);
        }

        return $query;
    }

    public function map($header): array
    {
        return [
            $header->invoice_code,
            $header->currency_name,
            optional($header->company)->company_name,
            optional($header->order)->order_code,
            optional($header->delivery)->delivery_code,
            optional($header->warehouse)->warehouse_code,
            optional($header->warehouse)->warehouse_name,
            optional($header->route)->route_code,
            optional($header->route)->route_name,
            optional($header->customer)->osa_code,
            optional($header->customer)->name,
            optional($header->salesman)->osa_code,
            optional($header->salesman)->name,
            $header->latitude,
            $header->longitude,
            $header->ura_invoice_no,
            $header->ura_antifake_code,
            $header->ura_qr_code,
            $header->purchaser_name,
            $header->purchaser_contact,
            $header->invoice_date,
            $header->invoice_time,
            $header->invoice_type,
            $header->gross_total,
            $header->vat,
            $header->pre_vat,
            $header->net_total,
            $header->promotion_total,
            $header->discount,
            $header->total_amount,
            $header->invoice_number,
            $header->status == 1 ? 'Active' : 'Inactive',
        ];
    }



    public function headings(): array
    {
        return [
            'Invoice Code',
            'Currency',
            'Company',
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
            'Latitude',
            'Longitude',
            'Ura Invoice No',
            'Ura Antifake Code',
            'Ura Qr Code',
            'Purchaser Name',
            'purchaser Contact',
            'Invoice Date',
            'Invoice Time',
            'Invoice Type',
            'Gross Total',
            'VAT',
            'Pre VAT',
            'Net Total',
            'Promotion Total',
            'Discount',
            'Total Amount',
            'Invoice Number',
            'Status',
        ];
    }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();
    //             $lastColumn = $sheet->getHighestColumn();

    //             // Header styling
    //             $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
    //                 'font' => [
    //                     'bold' => true,
    //                     'color' => ['rgb' => 'F5F5F5'],
    //                 ],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                     'vertical' => Alignment::VERTICAL_CENTER,
    //                 ],
    //                 'fill' => [
    //                     'fillType' => Fill::FILL_SOLID,
    //                     'startColor' => ['rgb' => '993442'],
    //                 ],
    //                 'borders' => [
    //                     'allBorders' => [
    //                         'borderStyle' => Border::BORDER_THIN,
    //                         'color' => ['rgb' => '000000'],
    //                     ],
    //                 ],
    //             ]);

    //             $sheet->getRowDimension(1)->setRowHeight(25);
    //         },
    //     ];
    // }
}