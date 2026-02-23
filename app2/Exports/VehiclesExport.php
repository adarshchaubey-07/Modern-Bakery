<?php
namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiclesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Vehicle::with([
            'warehouse:id,warehouse_code,warehouse_name,owner_name',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ]);

        foreach ($this->filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['vehicle_name', 'vehicle_code'])) {
                    $query->whereRaw("LOWER($field) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query->get()->map(function($vehicle) {
            return [
                $vehicle->vehicle_code,
                $vehicle->number_plat,
                $vehicle->vehicle_chesis_no,
                $vehicle->description,
                $vehicle->capacity,
                $vehicle->vehicle_type,
                $vehicle->vehicle_brand,
                $vehicle->owner_type,
                $vehicle->fuel_reading,
                $vehicle->valid_from,
                $vehicle->valid_to,
                $vehicle->opening_odometer,
                $vehicle->status == 1 ? 'Active' : 'Inactive',
                optional($vehicle->warehouse)->warehouse_name,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Vehicle Code',
            'Number Plate',
            'Chessis No',
            'Description',
            'Capacity',
            'Type',
            'Brand',
            'Owner Type',
            'Fuel Reading',
            'Valid From',
            'Valid To',
            'Opening Odometer',
            'Status',
            'Warehouse Name',
            // add other fields as needed
        ];
    }
}
