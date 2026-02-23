<?php

namespace App\Services\V1\Assets\Web;

use App\Models\AddChiller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class ChillerService
{
    /**
     * List with pagination and filters
     */
    public function all(int $perPage = 50, array $filters = [])
    {
        $query = AddChiller::latest()->with('vendor',);

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'serial_number'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

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


    /**
     * Create a new chiller
     */
    public function create(array $data): AddChiller
    {
        // dd($data);
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                $data['osa_code'] = $data['osa_code'] ?? $this->generateCode(),
                'uuid'        => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            // if (isset($data['vender_details']) && is_array($data['vender_details'])) {
            //     $data['vender_details'] = implode(',', $data['vender_details']);
            // }
            // dd($data);
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

    public function getBySerialNo(?string $serial)
    {
        if (!$serial) {
            return collect();
        }

        return AddChiller::query()
            ->select([
                'serial_number',
                'osa_code as chiller_code',       // 👈 changed here
                'assets_category',
                'model_number',
                'branding',
            ])
            ->with([
                'assetsCategory:id,name,osa_code as code',  // 👈 normalized as code
                'modelNumber:id,name,code as code',         // 👈 return field as code
                'brand:id,name,osa_code as code',           // 👈 normalized as code
            ])
            ->where('serial_number', 'ILIKE', "%{$serial}%")
            ->whereNull('deleted_at')
            ->get();
    }



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
}
