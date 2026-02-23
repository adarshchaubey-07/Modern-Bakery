<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\ItemCategory;
use App\Models\Uom;
use App\Models\UomType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ItemsImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows
{
    protected array $categoryCache = [];
    protected array $uomCache = [];
    protected array $uomTypeCache = [];

    protected array $uomIndexes = [2, 3, 4, 5, 6, 7, 8, 9];

    public function __construct()
    {
        $this->categoryCache = ItemCategory::pluck('id', 'category_code')->toArray();
        $this->uomCache = Uom::pluck('id', 'name')->toArray();
        $this->uomTypeCache = UomType::pluck('id', 'uom_type')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])
            ->toArray();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            DB::transaction(function () use ($row) {

                $categoryCode = $row['category_code'] ?? null;
                $categoryId   = $this->categoryCache[$categoryCode] ?? null;

                if (!$categoryId) {
                    throw new \Exception("Invalid category_code: {$categoryCode}");
                }
                $item = Item::create([
                    'id'          => $row['id'] ?? null,
                    'name'        => (string) $row['name'],
                    'code'        => (string) $row['code'],
                    'barcode'     => isset($row['barcode']) ? (string) $row['barcode'] : null,
                    'status'      => $row['status'] ?? 1,
                    'channel_id'  => $row['channel_id'] ?? null,
                    'category_id' => $categoryId,
                    'brand'       => 1,
                ]);
                $primaryUomName = $row['lowerunit'];
                $uoms = [];

                foreach ($this->uomIndexes as $index) {

                    $uomName = $row["uom{$index}"] ?? null;
                    if (!$uomName) {
                        continue;
                    }

                    $numerator   = (float) ($row["numerator{$index}"] ?? 0);
                    $denominator = (float) ($row["denominator{$index}"] ?? 1);

                    if ($denominator == 0) {
                        continue;
                    }

                    $uomId = $this->uomCache[$uomName] ?? null;
                    if (!$uomId) {
                        continue;
                    }

                    $uoms[] = [
                        'name'       => $uomName,
                        'uom_id'     => $uomId,
                        'upc'        => $numerator / $denominator,
                        'is_primary' => $uomName === $primaryUomName,
                    ];
                }

                foreach ($uoms as $uom) {
                    if ($uom['is_primary']) {
                        ItemUOM::create([
                            'item_id'  => $item->id,
                            'uom_type' => $this->uomTypeCache['primary'],
                            'name'     => $uom['name'],
                            'upc'      => $uom['upc'],
                            'price'    => 0,
                            'uom_id'   => $uom['uom_id'],
                        ]);
                        break;
                    }
                }
                $orderMap = [
                    1 => $this->uomTypeCache['secondary'] ?? null,
                    2 => $this->uomTypeCache['third'] ?? null,
                    3 => $this->uomTypeCache['fourth'] ?? null,
                    4 => $this->uomTypeCache['fifth'] ?? null,
                    5 => $this->uomTypeCache['sixth'] ?? null,
                    6 => $this->uomTypeCache['seventh'] ?? null,
                ];

                $sequence = 1;

                foreach ($uoms as $uom) {

                    if ($uom['is_primary']) {
                        continue;
                    }

                    $uomTypeId = $orderMap[$sequence] ?? null;
                    if (!$uomTypeId) {
                        continue;
                    }

                    ItemUOM::create([
                        'item_id'  => $item->id,
                        'uom_type' => $uomTypeId,
                        'name'     => $uom['name'],
                        'upc'      => $uom['upc'],
                        'price'    => 0,
                        'uom_id'   => $uom['uom_id'],
                    ]);

                    $sequence++;
                }
            });
        }
    }

    public function rules(): array
    {
        return [
            '*.name'          => 'required|string',
            '*.code'          => 'required',
            '*.category_code' => 'required|string',
            '*.lowerunit'     => 'required|string',
        ];
    }
}
