<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyCustomerExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'SAP Code',
            'OSA Code',
            'Business Name',
            'Language',
            'Town',
            'Landmark',
            'District',
            'Payment Type',
            'Credit Days',
            'TIN No',
            'Credit Limit',
            'Bank Guarantee Name',
            'Bank Guarantee Amount',
            'Bank Guarantee From',
            'Bank Guarantee To',
            'Total Credit Limit',
            'Credit Limit Validity',
            'Region',
            'Area',
            'Distribution Channel',
            'Status',
            'Contact Number',
            'Company Type',
        ];
    }
}
