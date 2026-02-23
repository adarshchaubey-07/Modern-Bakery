<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\PricingHeader;
use App\Models\PricingDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class PricingHeaderService
{
public function getAll(int $perPage = 50, array $filters = [])
{
    $query = PricingHeader::query()
        ->select([
            'id','uuid','code','name','description',
            'start_date','end_date','apply_on',
            'company_id',
            'item_id',
            'item_category_id','customer_id',
            'customer_category_id','outlet_channel_id',
            'applicable_for','status'
        ])
        ->with([
            'company:id,company_code,company_name',
            'item:id,code,name',
            'customer:id,osa_code,name',
            'customerCategory:id,customer_category_code,customer_category_name',
            'outletChannel:id,outlet_channel_code,outlet_channel',

        ])
        ->latest();

    foreach ($filters as $field => $value) {
        if (!empty($value)) {
            $query->where($field, $value);
        }
    }

    return $query->paginate($perPage);
}
    public function generateCode(): string
    {
        do {
            $lastPrice = PricingHeader::withTrashed()->latest('id')->first();
            $nextNumber = $lastPrice
                ? ((int) preg_replace('/\D/', '', $lastPrice->code)) + 1
                : 1;

            $code = 'PH' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (PricingHeader::withTrashed()->where('code', $code)->exists());

        return $code;
    }


    public function create(array $data): PricingHeader
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'code' => $data['code'] ?? $this->generateCode(),
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            if (PricingHeader::withTrashed()->where('code', $data['code'])->exists()) {
                throw new \Exception("The code '{$data['code']}' already exists.");
            }
            $pricingHeader = PricingHeader::create($data);
            DB::commit();
            return $pricingHeader;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";
            Log::error("PricingHeader creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);
            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function findByUuid(string $uuid): ?PricingHeader
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return PricingHeader::where('uuid', $uuid)->first();
    }


    public function updateByUuid(string $uuid, array $data): PricingHeader
    {
        $pricingHeader = $this->findByUuid($uuid);
        if (!$pricingHeader) {
            throw new \Exception("PricingHeader not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $pricingHeader->update($data);

            DB::commit();
            return $pricingHeader;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("PricingHeader update failed", [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'uuid'    => $uuid,
                'payload' => $data,
                'user'    => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function deleteByUuid(string $uuid): void
    {
        $pricingHeader = $this->findByUuid($uuid);
        if (!$pricingHeader) {
            throw new \Exception("PricingHeader not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $pricingHeader->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("PricingHeader delete failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'uuid'  => $uuid,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
    public function findItemPrice($itemId, $customerId = null, $routeId = null, $warehouseId = null)
    {
        if ($customerId) {
            $header = PricingHeader::where('customer_id', $customerId)->first();
            if ($header) {
                return PricingDetail::where('header_id', $header->id)
                    ->where('item_id', $itemId)
                    ->first();
            }
        }
        // dd($customerId);
        if ($routeId) {
            $header = PricingHeader::where('route_id', $routeId)->first();
            if ($header) {
                return PricingDetail::where('header_id', $header->id)
                    ->where('item_id', $itemId)
                    ->first();
            }
        }
        if ($warehouseId) {
            $header = PricingHeader::where('warehouse_id', $warehouseId)->first();
            if ($header) {
                return PricingDetail::where('header_id', $header->id)
                    ->where('item_id', $itemId)
                    ->first();
            }
        }
        return null;
    }
}
