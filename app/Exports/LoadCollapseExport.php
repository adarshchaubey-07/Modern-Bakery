<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
use App\Helpers\CommonLocationFilter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class LoadCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected array $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $filters;

    private const COLUMN_COUNT = 19;

    public function __construct($fromDate = null, $toDate = null, $filters = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->filters  = $filters;
    }

    public function collection()
    {
        $rows     = [];
        $rowIndex = 2;

        $query = LoadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'projecttype',
            'salesmantype',
            'details.item',
            'details.Uom',
        ]);

        $query = CommonLocationFilter::apply($query, $this->filters);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59',
            ]);
        }

        $headers = $query
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($headers as $header) {

            $headerRow = $rowIndex;

            $rows[] = array_pad([
                $header->osa_code ?? '',
                $header->delivery_no ?? '',
                optional($header->created_at)->format('d-m-Y'),
                $header->accept_time
                    ? Carbon::parse($header->accept_time)->format('d-m-Y')
                    : '',
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $header->salesmantype->salesman_type_name ?? '',
                $header->projecttype->name ?? '',
                $header->is_confirmed == 1
                    ? 'SalesTeam Accepted'
                    : 'Waiting For Accept',
                $header->details->count(),
            ], self::COLUMN_COUNT, '');

            $rowIndex++;
            $rows[] = array_pad([
                '',
                'Item',
                'UOM',
                'Quantity',
                'Price',
                'Net Price',
                'Batch No',
                'Batch Expiry Date',
                'MSP',
                'Display Unit',
            ], self::COLUMN_COUNT, '');

            $detailHeadingRow = $rowIndex;
            $rowIndex++;

            foreach ($header->details as $detail) {

                $rows[] = array_pad([
                    '',
                    trim(($detail->item->code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->Uom->name ?? '',
                    (float) ($detail->qty ?? 0),
                    (float) ($detail->price ?? 0),
                    (float) ($detail->net_price ?? 0),
                    $detail->batch_no ?? '',
                    $detail->batch_expiry_date ?? '',
                    $detail->msp ?? '',
                    $detail->displayunit ?? '',
                ], self::COLUMN_COUNT, '');

                $rowIndex++;
            }

            if ($detailHeadingRow + 1 < $rowIndex) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start'      => $detailHeadingRow,
                    'end'        => $rowIndex - 1,
                ];
            }

            $rows[] = array_fill(0, self::COLUMN_COUNT, '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Load No',
            'Delivery No',
            'Load Date',
            'Accept Date',
            'Route',
            'Salesman',
            'Salesman Type',
            'Project Type',
            'Status',
            'Item Count',
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
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
