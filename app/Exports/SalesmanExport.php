<?php

namespace App\Exports;

use App\Models\Salesman;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesmanExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $search;
    protected array $filters;
    protected array $columns;

    public function __construct(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $search = null,
        array $filters = [],
        array $columns = []
    ) {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->search   = $search;
        $this->filters  = $filters;
        $this->columns  = $columns;
    }

    public function collection(): Collection
    {
        $query = Salesman::with(['salesmanType','route','role', 'channel']);

        if ($this->search) {
            $like = '%' . strtolower($this->search) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(CAST(osa_code AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(contact_no AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(email AS TEXT)) LIKE ?', [$like]);
            });
        }

        foreach ($this->filters as $field => $value) {
            if ($value) {
                $query->where($field, $value);
            }
        }

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    private function columnMap($salesman): array
    {
        return [
            'Salesman Code' => $salesman->osa_code,
            'Salesman Name' => $salesman->name,
            'Salesman Type' => optional($salesman->salesmanType)->salesman_type_name,
            'Role Name' => optional($salesman->role)->name,
            'Channel Name' => optional($salesman->channel)->outlet_channel,
            'Route Code' => optional($salesman->route)->route_code,
            'Route Name' => optional($salesman->route)->route_name,
            'Email' => $salesman->email,
            'Designation' => $salesman->designation,
            'Security Code' => $salesman->security_code,
            'Device No' => $salesman->device_no,
            'Block Date To' => $salesman->block_date_to,
            'Block Date From' => $salesman->block_date_from,
            'Contact No' => $salesman->contact_no,
            'SAP ID' => $salesman->sap_id, 
            'Is Login' => $salesman->is_login ? 'Yes' : 'No',
            'Forceful Login' => $salesman->forceful_login ? 'Yes' : 'No',
            'Is Block' => $salesman->is_block ? 'Yes' : 'No',
            'Reason' => $salesman->reason,
            'Cashier Description Block' => $salesman->cashier_description_block,
            'Invoice Block' => $salesman->invoice_block,
            'Status' => $salesman->status == 1 ? 'Active' : 'Inactive',
        ];
    }

    public function map($salesman): array
    {
        $data = $this->columnMap($salesman);

        if (!empty($this->columns)) {
            return array_values(
                array_intersect_key($data, array_flip($this->columns))
            );
        }

        return array_values($data);
    }

    public function headings(): array
    {
        $all = array_keys($this->columnMap(new Salesman));

        if (!empty($this->columns)) {
            return array_values(array_intersect($all, $this->columns));
        }

        return $all;
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

                $sheet->getRowDimension(1)->setRowHeight(24);
            },
        ];
    }
}
