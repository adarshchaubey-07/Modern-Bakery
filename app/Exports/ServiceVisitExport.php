<?php

namespace App\Exports;

use App\Services\V1\Assets\Web\ServiceVisitService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServiceVisitExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(ServiceVisitService::class)->export(
            $this->filters
        );
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Ticket Type',
            'Time In',
            'Time Out',

            'Outlet Code',
            'Outlet Name',
            'Owner Name',
            'Contact No',

            'District',
            'Town / Village',
            'Location',

            'Model No',
            'Asset No',
            'Serial No',
            'Branding',

            'Machine Working',
            'Cleanliness',
            'Condenser Coil Cleaned',
            'Gaskets',
            'Light Working',

            'Work Status',
            'Complaint Type',
            'Comment',

            'Technician ID',
            'Created Date',
        ];
    }
}
