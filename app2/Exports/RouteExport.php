<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($item) {

            $item = (array) $item;

            return [
                'route_code'   => $item['route_code'] ?? '',
                'route_name'   => $item['route_name'] ?? '',
                'description'  => $item['description'] ?? '',
                'warehouse'    => $item['warehouse'] ?? '',
                'route_type'   => $item['route_type'] ?? '',
                'vehicle'      => $item['vehicle'] ?? '',
                'status'       => ($item['status'] == 1) ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Route Code',
            'Route Name',
            'Description',
            'Warehouse',
            'Route Type',
            'Vehicle',
            'Status'
        ];
    }
}
