<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HTReturnExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
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

        $query = HtReturnHeader::with([
            'warehouse',
            'company',
            'customer',
            'driver',
        ]);
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }
         $headers = $query->get();
         
        foreach ($headers as $header) {
            $rows[] = [
                'OSA Code'                  => $header->return_code,
                'Customer Code'             => $header->customer->osa_code ?? null,
                'Customer Name'             => $header->customer->business_name ?? null,
                'Company Code'              => $header->company->company_code ?? null,
                'Company Name'              => $header->company->company_name ?? null,
                'Warehouse Code'            => $header->warehouse->warehouse_code ?? null,
                'Warehouse Name'            => $header->warehouse->warehouse_name ?? null,
                'Driver Name'               => $header->driver->driver_name ?? null,
                'Driver Code'               => $header->driver->osa_code ?? null,
                // 'Truck Name'                => $header->truckname,
                'Truck No'                  => $header->truck_no,
                'Vat'                       => $header->vat,
                'Net'                       => $header->net,
                'Amount'                    => $header->amount,
                'Contact No'                => $header->contactno,
                'Sap Id'                    => $header->sap_id,                                           
                'Message'                   => $header->message,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Customer Code',
            'Customer Name',
            'Company Code',
            'Company Name',

            'Warehouse Code',
            'Warehouse Name',
            'Driver Name',
            'Driver Code',
            // 'Truck Name',
            'Truck No',
            'Vat',

            'Net',
            'Amount',
            'Contact No',
            'Sap Id',
            'Message',
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
