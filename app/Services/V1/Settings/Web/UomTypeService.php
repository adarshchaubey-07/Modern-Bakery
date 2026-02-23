<?php

namespace App\Services\V1\Settings\Web;

use App\Models\UomType;

class UomTypeService
{
    public function getList(): array
    {
        return UomType::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'uom_type'])
            ->toArray();
    }
    public function getIdByName(string $name): ?int
    {
        return UomType::whereRaw('LOWER(uom_type) = ?', [strtolower($name)])
            ->value('id');
    }
    public function getKeyValue(): array
    {
        return UomType::pluck('id', 'uom_type')
            ->mapWithKeys(fn ($id, $type) => [strtolower($type) => $id])
            ->toArray();
    }
}
