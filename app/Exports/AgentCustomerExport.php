<?php

namespace App\Exports;

use App\Models\AgentCustomer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AgentCustomerExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Name',
            'Customer Type',
            'TIN No',

            'Route Code',
            'Route Name',
            'Salesman Code',
            'Salesman Name',

            'Region Code',
            'Region Name',

            'Channel Code',
            'Channel Name',

            'Category Code',
            'Category Name',

            'Sub Category Code',
            'Sub Category Name',
            'Account Group Code',
            'Account Group Name',

            'City',
            'Landmark',
            'District',
            'Street',
            'Town',

            'Whatsapp No',
            'Contact No',
            'Contact No 2',

            'Payment Type',
            'Credit Days',
            'Credit Limit',

            'Latitude',
            'Longitude',
            'Divison',
            'Status',

            'Is Driver',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->osa_code,
            $customer->name,
            $customer->customer_type,
            $customer->tin_no,

            $customer->route?->route_code,
            $customer->route?->route_name,

            $customer->salesman?->osa_code,
            $customer->salesman?->name,

            $customer->region?->region_code,
            $customer->region?->region_name,

            $customer->outlet_channel?->outlet_channel_code,
            $customer->outlet_channel?->outlet_channel,

            $customer->category?->customer_category_code,
            $customer->category?->customer_category_name,

            $customer->subcategory?->customer_sub_category_code,
            $customer->subcategory?->customer_sub_category_name,
            $customer->accountgrp?->code,
            $customer->accountgrp?->name,

            $customer->city,
            $customer->landmark,
            $customer->district,
            $customer->street,
            $customer->town,

            $customer->whatsapp_no,
            $customer->contact_no,
            $customer->contact_no2,

            $customer->payment_type,
            $customer->creditday,
            $customer->credit_limit,

            $customer->latitude,
            $customer->longitude,
            $customer->divison, 
            $customer->status == 1 ? 'Active' : 'Inactive',

            $customer->is_driver,
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
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
