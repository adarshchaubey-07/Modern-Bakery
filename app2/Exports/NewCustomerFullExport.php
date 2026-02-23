<?php

namespace App\Exports;

use App\Models\Agent_Transaction\NewCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class NewCustomerFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $rows = [];
        $query = NewCustomer::with([
            'customer',
            'customertype',
            'route',
            'outlet_channel',
            'category',
            'subcategory',
            'getWarehouse'
        ]);
        if ($this->uuid) {
            $query->where('uuid', $this->uuid);
        }
        $customers = $query->get();
        foreach ($customers as $customer) {
            $rows[] = [
                'OSA Code' => (string) $customer->osa_code,
                'Outlet Name' => (string) $customer->name,
                'Owner Name' => (string) $customer->owner_name,

                'Customer Name' => (string) ($customer->customer->name ?? ''),

                'Customer Type' => (string) ($customer->customertype->name ?? ''),
                'Customer Type Code' => (string) ($customer->customertype->code ?? ''),

                'Route Name' => (string) ($customer->route->route_name ?? ''),

                'Outlet Channel' => (string) ($customer->outlet_channel->outlet_channel ?? ''),

                'Category Name' => (string) ($customer->category->customer_category_name ?? ''),

                'Sub Category Name' => (string) ($customer->subcategory->customer_sub_category_name ?? ''),

                'Warehouse Name' => (string) ($customer->getWarehouse->warehouse_name ?? ''),

                'Landmark' => (string) ($customer->landmark ?? ''),
                'District' => (string) ($customer->district ?? ''),
                'Street' => (string) ($customer->street ?? ''),
                'Town' => (string) ($customer->town ?? ''),

                'WhatsApp No' => (string) ($customer->whatsapp_no ?? ''),
                'Contact No 1' => (string) ($customer->contact_no ?? ''),
                'Contact No 2' => (string) ($customer->contact_no2 ?? ''),

                'Payment Type' => match ((int) ($customer->payment_type ?? 0)) {
                    1 => 'cash',
                    2 => 'cheque',
                    3 => 'transfer',
                    default => '',
                },

                'Credit Days' => (string) ($customer->creditday ?? ''),
                'Credit Limit' => (float) ($customer->credit_limit ?? 0),

                'Latitude' => (string) ($customer->latitude ?? ''),
                'Longitude' => (string) ($customer->longitude ?? ''),

                // 'Approval Status' => (string) ($customer->approval_status ?? ''),
                'Approval Status' => match ((int) ($customer->approval_status ?? 0)) {
                    1 => 'Approved',
                    2 => 'Pending',
                    3 => 'Rejected',
                    default => '',
                },
                'Reject Reason' => (string) ($customer->reject_reason ?? ''),

                'Status' => $customer->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Outlet Name',
            'Owner Name',
            'Customer Name',
            'Customer Type',
            'Customer Type Code',
            'Route Name',
            'Outlet Channel',
            'Category Name',
            'Sub Category Name',
            'Warehouse Name',
            'Landmark',
            'District',
            'Street',
            'Town',
            'WhatsApp No',
            'Contact No 1',
            'Contact No 2',
            'Payment Type',
            'Credit Days',
            'Credit Limit',
            'Latitude',
            'Longitude',
            'Approval Status',
            'Reject Reason',
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
