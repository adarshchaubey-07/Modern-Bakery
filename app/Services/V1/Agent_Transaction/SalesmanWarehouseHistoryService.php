<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\SalesmanWarehouseHistory;
use Throwable;
use Log;

class SalesmanWarehouseHistoryService
{
    public function list(int $perPage = 50, array $filters = [])
    {
        try {
            $query = SalesmanWarehouseHistory::with([
                'salesman:id,sub_type,osa_code,name',
                'salesman.subtype:id,osa_code,name'
            ]);

            // Optional filters
            if (!empty($filters['salesman_id'])) {
                $query->where('salesman_id', $filters['salesman_id']);
            }

            if (!empty($filters['warehouse_id'])) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }

            if (!empty($filters['manager_id'])) {
                $query->where('manager_id', $filters['manager_id']);
            }

            if (!empty($filters['route_id'])) {
                $query->where('route_id', $filters['route_id']);
            }

            return $query
                ->orderBy('id', 'desc')
                ->paginate($perPage);
        } catch (Throwable $e) {
            dd($e);
            Log::error('Failed to fetch salesman warehouse history list', [
                'error'   => $e->getMessage(),
                'filters' => $filters,
            ]);

            throw new \Exception('Unable to fetch salesman warehouse history list.');
        }
    }
}
