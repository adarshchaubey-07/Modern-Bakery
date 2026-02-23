<?php

namespace App\Services\V1\Assets\Web;

use App\Models\AddChiller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;
use App\Models\FrigeCustomerUpdate;
use App\Models\ChillerRequest;
use App\Models\ChillerUpdateWarehouse;
use Illuminate\Http\Request;

class ChillerService
{
    /**
     * List with pagination and filters
     */
    public function all(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $query = AddChiller::query()
            ->when(!$dropdown, fn($q) => $q->with('vendor'))
            ->latest();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'serial_number'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }
        }

        // 🔹 Dropdown → only serial_number, no pagination
        if ($dropdown) {
            return $query
                ->reorder()
                ->whereNotNull('serial_number')
                ->groupBy('serial_number')
                ->orderBy('serial_number')
                ->pluck('serial_number');
        }
        // 🔹 Normal list → pagination
        return $query->paginate($perPage);
    }


    /**
     * Generate unique fridge code
     */
    public function generateCode(): string
    {
        do {
            $last = AddChiller::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'CH' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (AddChiller::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): AddChiller
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                $data['osa_code'] = $data['osa_code'] ?? $this->generateCode(),
                'uuid'        => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            $chiller = AddChiller::create($data);
            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
            Log::error("Chiller creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);
            throw new \Exception($friendlyMessage, 0, $e);
        }
    }


    /**
     * Find chiller by UUID (validates UUID format)
     */
    public function findByUuid(string $uuid): ?AddChiller
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return AddChiller::where('uuid', $uuid)->first();
    }

    /**
     * Update chiller by UUID
     */
    public function updateByUuid(string $uuid, array $data): AddChiller
    {
        $chiller = $this->findByUuid($uuid);

        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            // Convert vender_details array → CSV string
            // if (isset($data['vender_details']) && is_array($data['vender_details'])) {
            //     $data['vender_details'] = implode(',', $data['vender_details']);
            // }

            // Update
            $chiller->fill($data);
            $chiller->save();

            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Chiller update failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }
    /**
     * Delete chiller by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $chiller = $this->findByUuid($uuid);
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $chiller->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Chiller delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    // public function getBySerialNo(?string $serial)
    // {
    //     if (!$serial) {
    //         return collect();
    //     }

    //     return AddChiller::query()
    //         ->select([
    //             'serial_number',
    //             'osa_code as chiller_code',       // 👈 changed here
    //             'assets_category',
    //             'model_number',
    //             'branding',
    //         ])
    //         ->with([
    //             'assetsCategory:id,name,osa_code as code',  // 👈 normalized as code
    //             'modelNumber:id,name,code as code',         // 👈 return field as code
    //             'brand:id,name,osa_code as code',           // 👈 normalized as code
    //         ])
    //         ->where('serial_number', 'ILIKE', "%{$serial}%")
    //         ->whereNull('deleted_at')
    //         ->get();
    // }



    public function globalSearch(?string $query)
    {
        if (!$query) {
            return collect();
        }

        return AddChiller::query()
            ->select([
                'id',
                'uuid',
                'osa_code',
                'sap_code',
                'serial_number',
                'acquisition',
                'assets_type',
                'country_id',
                'vender',
                'assets_category',
                'model_number',
                'manufacturer',
                'branding',
                'status',
                'remarks',
                'trading_partner_number',
                'capacity',
                'manufacturing_year',
            ])
            ->with([
                'country:id,country_code,country_name',
                'vendor:id,code,name',
                'assetsCategory:id,osa_code,name',
                'modelNumber:id,code,name',
                'manufacture:id,osa_code,name',
                'brand:id,osa_code,name',
            ])
            ->where(function ($q) use ($query) {
                $q->where('serial_number', 'ILIKE', "%{$query}%")
                    ->orWhere('osa_code', 'ILIKE', "%{$query}%")
                    ->orWhere('sap_code', 'ILIKE', "%{$query}%")
                    ->orWhere('model_number', 'ILIKE', "%{$query}%")
                    ->orWhere('vender', 'ILIKE', "%{$query}%")
                    ->orWhere('assets_type', 'ILIKE', "%{$query}%")
                    ->orWhere('trading_partner_number', 'ILIKE', "%{$query}%")
                    ->orWhere('capacity', 'ILIKE', "%{$query}%")
                    ->orWhere('manufacturing_year', 'ILIKE', "%{$query}%");
            })
            ->whereNull('deleted_at')
            ->get();
    }

    public function transfer(int $fromWarehouseId, int $toWarehouseId): void
    {
        DB::transaction(function () use ($fromWarehouseId, $toWarehouseId) {

            $fridgeIds = AddChiller::where('warehouse_id', $fromWarehouseId)
                ->pluck('id')
                ->toArray();

            $chillerIds = FrigeCustomerUpdate::where('warehouse_id', $fromWarehouseId)
                ->pluck('id')
                ->toArray();

            $chillerRequestIds = ChillerRequest::where('warehouse_id', $fromWarehouseId)
                ->pluck('id')
                ->toArray();

            if (!empty($fridgeIds)) {
                AddChiller::whereIn('id', $fridgeIds)
                    ->update(['warehouse_id' => $toWarehouseId]);
            }

            if (!empty($chillerIds)) {
                FrigeCustomerUpdate::whereIn('id', $chillerIds)
                    ->update(['warehouse_id' => $toWarehouseId]);
            }

            if (!empty($chillerRequestIds)) {
                ChillerRequest::whereIn('id', $chillerRequestIds)
                    ->update(['warehouse_id' => $toWarehouseId]);
            }

            ChillerUpdateWarehouse::create([
                'uuid'               => Str::uuid(),
                'fridge_id'          => $fridgeIds ? implode(',', $fridgeIds) : null,
                'chiller_id'         => $chillerIds ? implode(',', $chillerIds) : null,
                'chiller_request_id' => $chillerRequestIds ? implode(',', $chillerRequestIds) : null,
                'from_warehouse_id'  => $fromWarehouseId,
                'to_warehouse_id'    => $toWarehouseId,
            ]);
        });
    }

    public function transferlist(Request $request)
    {
        $query = ChillerUpdateWarehouse::query()
            ->with([
                'fromWarehouse:id,warehouse_name,warehouse_code',
                'toWarehouse:id,warehouse_name,warehouse_code',
                'fridge:id,osa_code,serial_number,assets_type',
                'chiller:id,osa_code,owner_name',
                'chillerrequest:id,osa_code,owner_name'
            ]);

        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if (
            !$request->filled('from_warehouse_id') &&
            !$request->filled('to_warehouse_id') &&
            $request->filled('warehouse_id')
        ) {
            $query->where(function ($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                    ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return $query
            ->orderByDesc('id')
            ->paginate($request->get('limit', 20));
    }

    public function filterByStatus(array $filters = [], int $perPage = 10)
    {
        // dd($filters);
        $query = AddChiller::query()
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }


        if (!empty($filters['model_number'])) {
            $query->where('model_number', 'ILIKE', '%' . $filters['model_number'] . '%');
        }

        return $query->paginate($perPage);
    }

    public function filterData(array $filters = [], int $perPage = 10)
    {
        $query = AddChiller::query()
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        /**
         * 🔹 Status filter (single or multiple)
         * status=1 OR status=1,2
         */
        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status'])
                ? $filters['status']
                : explode(',', $filters['status']);

            $query->whereIn('status', $statuses);
        }

        /**
         * 🔹 Model filter (model_id)
         * model_id=5 OR model_id=5,6
         */
        if (!empty($filters['model_id'])) {
            $modelIds = is_array($filters['model_id'])
                ? $filters['model_id']
                : explode(',', $filters['model_number']);

            $query->whereIn('model_number', $modelIds);
        }

        /**
         * 🔹 Warehouse filter (single or multiple)
         * warehouse_id=10 OR warehouse_id=10,11
         */
        if (!empty($filters['warehouse_id'])) {
            $warehouseIds = is_array($filters['warehouse_id'])
                ? $filters['warehouse_id']
                : explode(',', $filters['warehouse_id']);

            $query->whereIn('warehouse_id', $warehouseIds);
        }

        return $query->paginate($perPage);
    }


    public function getByWarehouseId(array $warehouseIds, int $perPage = 10)
    {
        return AddChiller::query()
            ->whereNull('deleted_at')
            ->whereIn('warehouse_id', $warehouseIds)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
