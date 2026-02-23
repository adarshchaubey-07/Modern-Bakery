<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
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

class ReturnHCollapseExport implements 
    FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithEvents, 
    WithStyles
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

        $query = HtReturnHeader::with([
            'customer',
            'company',
            'warehouse',
            'driver',
        ]);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            $rows[] = [
                'Return Code'       => (string) $header->return_code,
                'Customer Code'     => (string) ($header->customer->osa_code ?? ''),
                'Customer Name'     => (string) ($header->customer->business_name ?? ''),
                'Customer Email'    => (string) ($header->customer->email ?? ''),
                'Customer Town'     => (string) ($header->customer->town ?? ''),
                'Customer Street'   => (string) ($header->customer->street ?? ''),
                'Customer Contact'  => (string) ($header->customer->contact_no ?? ''),

                'Company Code'      => (string) ($header->company->company_code ?? ''),
                'Company Name'      => (string) ($header->company->company_name ?? ''),

                'Warehouse Code'    => (string) ($header->warehouse->warehouse_code ?? ''),
                'Warehouse Name'    => (string) ($header->warehouse->warehouse_name ?? ''),

                'Driver Name'       => (string) ($header->driver->driver_name ?? ''),
                'Driver Code'       => (string) ($header->driver->osa_code ?? ''),

                'VAT'               => (float) $header->vat,
                'Net'               => (float) $header->net,
                'Amount'            => (float) $header->amount,
                'Truck Name'        => (string) $header->truckname,
                'Truck No'          => (string) $header->truckno,
                'Contact No'        => (string) $header->contactno,
                'SAP ID'            => (string) $header->sap_id,
                'Message'           => (string) $header->message,

                'POSHR'             => '',
                'Item Code'         => '',
                'Item Name'         => '',
                'Item Value'        => '',
                'Detail VAT'        => '',
                'UOM Name'          => '',
                'Qty'               => '',
                'Net Detail'        => '',
                'Total'             => '',
                'Expiry Batch'      => '',
                'Reason'            => '',
                'Reason Type'       => '',
                'Batch No'          => '',
                'Actual Expiry'     => '',
                'Remark'            => '',
                'Invoice SAP ID'    => '',
            ];

            $rowIndex++;

            $details = HtReturnDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'Return Code'       => '',
                    'Customer Code'     => '',
                    'Customer Name'     => '',
                    'Customer Email'    => '',
                    'Customer Town'     => '',
                    'Customer Street'   => '',
                    'Customer Contact'  => '',

                    'Company Code'      => '',
                    'Company Name'      => '',

                    'Warehouse Code'    => '',
                    'Warehouse Name'    => '',

                    'VAT'               => '',
                    'Net'               => '',
                    'Amount'            => '',
                    'Truck Name'        => '',
                    'Truck No'          => '',
                    'Contact No'        => '',
                    'SAP ID'            => '',
                    'Message'           => '',

                    'POSHR'             => (string) $detail->poshr,
                    'Item Code'         => (string) ($detail->item->code ?? ''),
                    'Item Name'         => (string) ($detail->item->name ?? ''),
                    'Item Value'        => (float) $detail->item_value,
                    'Detail VAT'        => (float) $detail->vat,
                    'UOM Name'          => (string) ($detail->uom->name ?? ''),
                    'Qty'               => (float) $detail->qty,
                    'Net Detail'        => (float) $detail->net,
                    'Total'             => (float) $detail->total,
                    'Expiry Batch'      => (string) $detail->expiry_batch,
                    'Reason'            => (string) $detail->reason,
                    'Reason Type'       => (string) $detail->reason_type,
                    'Batch No'          => (string) $detail->batchno,
                    'Actual Expiry'     => (string) $detail->actual_expiry_date,
                    'Remark'            => (string) $detail->remark,
                    'Invoice SAP ID'    => (string) $detail->invoice_sap_id,
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
            'Return Code',
            'Customer Code',
            'Customer Name',
            'Customer Email',
            'Customer Town',
            'Customer Street',
            'Customer Contact',

            'Company Code',
            'Company Name',

            'Warehouse Code',
            'Warehouse Name',

            'Driver Name',
            'Driver Code',

            'VAT',
            'Net',
            'Amount',
            'Truck Name',
            'Truck No',
            'Contact No',
            'SAP ID',
            'Message',

            'POSHR',
            'Item Code',
            'Item Name',
            'Item Value',
            'Detail VAT',
            'UOM Name',
            'Qty',
            'Net Detail',
            'Total',
            'Expiry Batch',
            'Reason',
            'Reason Type',
            'Batch No',
            'Actual Expiry',
            'Remark',
            'Invoice SAP ID'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AJ1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AJ1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
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
