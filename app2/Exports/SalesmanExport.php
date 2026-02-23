<?php

namespace App\Exports;

use App\Models\Salesman;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class SalesmanExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?string $fromDate;
    protected ?string $toDate;

    public function __construct(?string $fromDate = null, ?string $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection(): Collection
    {
        $query = Salesman::with(['salesmanType', 'route', 'warehouse', 'subtype']);

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    public function map($salesman): array
    {
        $status = $salesman->status == 1 ? 'Active' : 'Inactive';

        return [
            $salesman->osa_code,
            $salesman->name,
            optional($salesman->salesmanType)->salesman_type_name,

            // Project (coming from subtype relation)
            optional($salesman->subtype)->name,

            $salesman->designation,
            $salesman->security_code,
            $salesman->device_no,
            optional($salesman->route)->route_name,
            $salesman->block_date_to,
            $salesman->block_date_from,
            $salesman->contact_no,
            optional($salesman->warehouse)->warehouse_name,
            optional($salesman->warehouse)->owner_name,
            $salesman->sap_id,
            $salesman->is_login ? 'Yes' : 'No',
            $status,
            $salesman->email,
            $salesman->forceful_login ? 'Yes' : 'No',
            $salesman->is_block ? 'Yes' : 'No',
            $salesman->reason,
            $salesman->cashier_description_block,
            $salesman->invoice_block,
        ];
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Name',
            'Salesman Type Name',
            'Project',
            'Designation',
            'Security Code',
            'Device No',
            'Route Name',
            'Block Date To',
            'Block Date From',
            'Contact No',
            'Warehouse Name',
            'Warehouse Owner Name',
            'SAP ID',
            'Is Login',
            'Status',
            'Email',
            'Forceful Login',
            'Is Block',
            'Reason',
            'Cashier Description Block',
            'Invoice Block'
        ];
    }
}
