<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CallRegisterExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
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

    public function map($row): array
    {
        return [
            // $row->id,
            $row->osa_code,
            $row->ticket_type,
            $row->ticket_date,
            $row->ticket_no,
            $row->chiller_code,

            // ✅ TECHNICIAN CODE + NAME
            $row->technician?->osa_code ?? '',
            $row->technician?->name ?? '',

            // ✅ CUSTOMER CODE + NAME
            // $row->customer?->osa_code ?? '',
            // $row->customer?->name ?? '',

            $row->chiller_serial_number,
            $row->model_number,
            $row->brand,
            $row->outlet_name,
            $row->owner_name,
            $row->town,
            $row->district,
            $row->contact_no1,
            $row->contact_no2,
            $row->nature_of_call,
            $row->created_at,
            $row->completion_date,
            $row->status,
        ];
    }

    public function headings(): array
    {
        return [

            // 'Id',
            'Osa Code',
            'Ticket Type',
            'Ticket Date',
            'Ticket No',
            'Chiller Code',

            'Technician Code',
            'Technician Name',

            // 'Customer Code',
            // 'Customer Name',

            'Asset Number',
            'Model Number',
            'Brand',
            'Outlet Name',
            'Owner Name',
            'Town',
            'District',
            'Contact No 1',
            'Contact No 2',
            'Nature Of Call',
            'Created At',
            'Completion Date',
            'Status',


        ];
    }
}
