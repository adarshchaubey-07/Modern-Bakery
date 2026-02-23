<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ItemExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;
    protected $columns;

    protected array $columnMap = [
        'Item Code'            => 'code',
        'Item Name'            => 'name',
        'Description'          => 'description',
        'Image'                => 'image',
        'Category Name'        => 'category_name',
        'Shelf Life'           => 'shelf_life',
        'Brand'                => 'brand',
        'Item Weight'          => 'item_weight',
        'Volume'               => 'volume',
        'Is Promotional'       => 'is_promotional',
        'Is Taxable'           => 'is_taxable',
        'Has Excise'           => 'has_excies',
        'Commodity Goods Code' => 'commodity_goods_code',
        'Excise Duty Code'     => 'excise_duty_code',
        'Base UOM Vol'         => 'base_uom_vol',
        'Alt Base UOM Vol'     => 'alter_base_uom_vol',
        'Distribution Code'    => 'distribution_code',
        'Barcode'              => 'barcode',
        'Net Weight'           => 'net_weight',
        'Tax'                  => 'tax',
        'VAT'                  => 'vat',
        'Excise'               => 'excise',
        'UOM Efris Code'       => 'uom_efris_code',
        'Alt UOM Efris Code'   => 'altuom_efris_code',
        'Item Group'           => 'item_group',
        'Item Group Desc'      => 'item_group_desc',
        'Caps Promo'           => 'caps_promo',
        'Sequence No'          => 'sequence_no',
        'UOM Name'             => 'uom_name',
        'UOM Type'             => 'uom_type',
        'UPC'                  => 'upc',
        'Price'                => 'price',
        'Is Stock Keeping'     => 'is_stock_keeping',
        'Enable For'           => 'enable_for',
        'Keeping Quantity'     => 'keeping_quantity',
        'Status'               => 'status',
    ];

    public function __construct($data, array $columns = [])
    {
        $this->data = collect($data);

        $this->columns = empty($columns)
            ? array_keys($this->columnMap)
            : array_values(array_intersect($columns, array_keys($this->columnMap)));
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($row): array
    {
        $mapped = [];

        foreach ($this->columns as $label) {
            $key = $this->columnMap[$label];
            $mapped[] = $row[$key] ?? '';
        }

        return $mapped;
    }

    public function headings(): array
    {
        return $this->columns;
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
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '993442'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(25);

            $highestColumnIndex = Coordinate::columnIndexFromString($lastColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
        },
    ];
}

}
