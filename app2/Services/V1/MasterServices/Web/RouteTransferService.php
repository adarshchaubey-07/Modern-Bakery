<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\AgentCustomer;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RouteTransferService
{
    public function transferRoute(array $data): array
    {
        DB::beginTransaction();

        try {
            // 🔹 Fetch new route warehouse
            $route = Route::select('id', 'warehouse_id')
                ->where('id', $data['new_route_id'])
                ->first();

            if (!$route) {
                Log::channel('route_transfer')->error('[ROUTE TRANSFER FAILED - INVALID ROUTE]', [
                    'new_route_id' => $data['new_route_id'],
                    'performed_by' => auth()->id(),
                    'datetime'     => now()->toDateTimeString(),
                ]);

                throw new \Exception('Invalid new route selected.');
            }

            // 🔹 Fetch customers on old route
            $customers = AgentCustomer::where('route_id', $data['old_route_id'])
                ->whereNull('deleted_at')
                ->get();

            if ($customers->isEmpty()) {
                Log::channel('route_transfer')->warning('[ROUTE TRANSFER FAILED - NO DATA]', [
                    'old_route_id' => $data['old_route_id'],
                    'new_route_id' => $data['new_route_id'],
                    'performed_by' => auth()->id(),
                    'datetime'     => now()->toDateTimeString(),
                ]);

                throw new \Exception('No customers found on the selected route.');
            }

            // ✅ Collect old warehouse(s) BEFORE update
            $oldWarehouses = $customers
                ->pluck('warehouse')
                ->unique()
                ->values()
                ->toArray();

            // 🔹 START LOG
            Log::channel('route_transfer')->info('[ROUTE TRANSFER START]', [
                'old_route_id'      => $data['old_route_id'],
                'new_route_id'      => $data['new_route_id'],
                'old_warehouse_ids' => $oldWarehouses, // ✅ added
                'new_warehouse_id'  => $route->warehouse_id,
                'total_customers'   => $customers->count(),
                'performed_by'      => auth()->id(),
                'datetime'          => now()->toDateTimeString(),
            ]);

            foreach ($customers as $customer) {

                // 🔹 PER CUSTOMER LOG (already correct)
                Log::channel('route_transfer')->info('[ROUTE TRANSFER CUSTOMER]', [
                    'customer_id'    => $customer->id,
                    'customer_uuid'  => $customer->uuid,
                    'old_route_id'   => $data['old_route_id'],
                    'new_route_id'   => $data['new_route_id'],
                    'old_warehouse'  => $customer->warehouse,
                    'new_warehouse'  => $route->warehouse_id,
                    'datetime'       => now()->toDateTimeString(),
                ]);

                // ✅ Update route + warehouse
                $customer->route_id = $data['new_route_id'];
                $customer->warehouse = $route->warehouse_id;
                $customer->updated_user = auth()->id();
                $customer->save();
            }

            DB::commit();

            // ✅ SUCCESS LOG
            Log::channel('route_transfer')->info('[ROUTE TRANSFER COMPLETED]', [
                'old_route_id'      => $data['old_route_id'],
                'new_route_id'      => $data['new_route_id'],
                'old_warehouse_ids' => $oldWarehouses, // ✅ added
                'new_warehouse_id'  => $route->warehouse_id,
                'customers_updated' => $customers->count(),
                'performed_by'      => auth()->id(),
                'datetime'          => now()->toDateTimeString(),
            ]);

            return [
                'old_route_id'      => $data['old_route_id'],
                'new_route_id'      => $data['new_route_id'],
                'new_warehouse_id'  => $route->warehouse_id,
                'customers_updated' => $customers->count(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            // ❌ FAILURE LOG
            Log::channel('route_transfer')->error('[ROUTE TRANSFER FAILED - EXCEPTION]', [
                'old_route_id'      => $data['old_route_id'] ?? null,
                'new_route_id'      => $data['new_route_id'] ?? null,
                'old_warehouse_ids' => $oldWarehouses ?? [], // ✅ added safely
                'error'             => $e->getMessage(),
                'performed_by'      => auth()->id(),
                'datetime'          => now()->toDateTimeString(),
            ]);

            throw new \Exception(
                'Route transfer failed. ' . $e->getMessage()
            );
        }
    }
}
