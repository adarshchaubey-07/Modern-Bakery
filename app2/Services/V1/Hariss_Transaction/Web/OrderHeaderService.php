<?php

namespace App\Services\V1\Hariss_transaction\Web;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderHeaderService
{
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HTOrderHeader::latest();
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'LIKE', "%$search%")
                    ->orWhere('comment', 'LIKE', "%$search%")
                    ->orWhere('status', 'LIKE', "%$search%");
            });
        }

        foreach (
            [
                'customer_id',
                'salesman_id',
                'country_id',
                'status'
            ] as $field
        ) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->order_code,
                    'value' => $item->id,
                ];
            });
        }
        return $query->paginate($perPage);
    }


    public function getByUuid(string $uuid)
    {
        try {

            $current = HTOrderHeader::with([
                'details' => function ($q) {
                    $q->with(['item', 'uom']);
                },
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }
            $previousUuid = HTOrderHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = HTOrderHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;

            return $current;
        } catch (\Exception $e) {
            Log::error("OrderHeaderService::getByUuid Error: " . $e->getMessage());
            return null;
        }
    }
}
