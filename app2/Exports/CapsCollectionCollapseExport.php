<?php

namespace App\Exports;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class CapsCollectionCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $from;
    protected $to;
    protected $groups = [];


    public function collection()
    {
        $rows = [];
        $rowIndex = 2; // row 1 = headings

        $headers = CapsCollectionHeader::with([
            'warehouse',
            'route',
            'salesman',
            'customer'
        ])
            ->when($this->from, fn($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('date', '<=', $this->to))
            ->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            // HEADER ROW
            $rows[] = [
                $header->code,
                $header->warehouse->warehouse_code ?? '',
                $header->warehouse->warehouse_name ?? '',
                $header->route->route_code ?? '',
                $header->route->route_name ?? '',
                $header->salesman->osa_code ?? '',
                $header->salesman->name ?? '',
                $header->customer ?? '',
                $header->date ?? '',
                '', '', '', '', '', '', ''
            ];

            $rowIndex++;

            // DETAILS
            $details = CapsCollectionDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $d) {

                $rows[] = [
                    '', '', '', '', '', '', '', '', '',
                    $d->item->code ?? '',
                    $d->item->name ?? '',
                    $d->uom->name ?? '',
                    (string) $d->collected_quantity,
                    $d->status == 1 ? 'Active' : 'Inactive',
                    '', ''
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }

            if (count($detailRowIndexes) > 0) {
                $this->groups[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            // HEADER FIELDS
            'Code',
            'Warehouse Code',
            'Warehouse Name',
            'Route Code',
            'Route Name',
            'Salesman Code',
            'Salesman Name',
            'Customer',
            'Date',

            // DETAIL FIELDS
            'Item Code',
            'Item Name',
            'UOM Name',
            'Collected Qty',
            'Status',
            '',
            ''
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {

                $sheet = $e->sheet->getDelegate();

                foreach ($this->groups as $g) {
                    for ($i = $g['start']; $i <= $g['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
