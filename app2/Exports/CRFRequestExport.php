<?php

namespace App\Exports;

use App\Services\V1\Assets\Web\ChillerRequestService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CRFRequestExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return app(ChillerRequestService::class)->exportCRFRequests(
            $this->filters['status'] ?? null,
            $this->filters['region_id'] ?? [],
            $this->filters['user_id'] ?? [],
            $this->filters['warehouse_id'] ?? [],
            $this->filters['route_id'] ?? [],
            $this->filters['salesman_id'] ?? [],
            $this->filters['model_id'] ?? []
        );
    }

    public function headings(): array
    {
        return [
            'IRO Status',
            'CRF ID',
            'Customer Name',
            'Customer Code',
            'City',
            'District',
            'Phone1',
            'Phone2',

            'Fridge Code',
            'Serial Number',
            'Model Number',
            'Type',

            'Warehouse Name',
            'Warehouse Code',

            'Region Code',
            'Region Name',

            'Route Code',
            'Route Name',

            'Salesman Code',
            'Salesman Name',

            'Created Date'
        ];
    }
}
