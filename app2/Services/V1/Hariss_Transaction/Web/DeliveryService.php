<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HTDeliveryHeader::latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('delivery_code', 'LIKE', "%$search%")
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
        $sortBy    = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->delivery_code,
                    'value' => $item->id,
                ];
            });
        }

        return $query->paginate($perPage);
    }


     public function getByUuid(string $uuid)
{
    try {

        $current = HTDeliveryHeader::with([
            'details.item',
            'details.itemuom'
        ])->where('uuid', $uuid)->first();

        if (!$current) {
            return null; 
        }
        $previousUuid = HTDeliveryHeader::where('id', '<', $current->id)
            ->orderBy('id', 'desc')
            ->value('uuid');

        $nextUuid = HTDeliveryHeader::where('id', '>', $current->id)
            ->orderBy('id', 'asc')
            ->value('uuid');

        $current->previous_uuid = $previousUuid;
        $current->next_uuid = $nextUuid;

        return $current;

    } catch (\Exception $e) {
        Log::error("DeliveryService::getByUuid Error: " . $e->getMessage());
        return null;
    }
}
}
