<?php

namespace App\Services\V1\Assets\Web;

use App\Models\CallRegister;
use App\Models\AddChiller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CallRegisterService
{
    /**
     * Fetch all with filter + pagination
     */
    public function getAll(int $perPage = 50, array $filters = [])
    {
        try {
            $query = CallRegister::query();

            foreach ($filters as $key => $value) {
                if (empty($value)) continue;

                if (in_array($key, ['ticket_no', 'osa_code', 'outlet_name', 'owner_name'])) {
                    $query->whereRaw("LOWER({$key}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($key, $value);
                }
            }

            $query->orderBy('created_at', 'desc');

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("CallRegister fetch failed", [
                'error'   => $e->getMessage(),
                'filters' => $filters
            ]);

            throw new \Exception("Failed to fetch Call Register list", 0, $e);
        }
    }


    /**
     * Generate next OSA Code
     */
    public function generateCode(?string $inputCode = null): string
    {
        // 1️⃣ If code is provided, return it directly
        if (!empty($inputCode)) {
            return $inputCode;
        }

        // 2️⃣ Else auto-generate code
        do {
            $last = CallRegister::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'BD' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (CallRegister::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }



    /**
     * Create record
     */
    public function create(array $data): CallRegister
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'osa_code' => $data['osa_code'] ?? $this->generateCode(),
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
                'created_user' => Auth::id(),
            ]);

            $record = CallRegister::create($data);

            DB::commit();

            return $record;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("CallRegister create failed", [
                'error' => $e->getMessage(),
                'data'  => $data,
                'user'  => Auth::id()
            ]);

            throw new \Exception("Something went wrong while creating Call Register", 0, $e);
        }
    }


    /**
     * Find by UUID
     */
    public function findByUuid(string $uuid): ?CallRegister
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        return CallRegister::where('uuid', $uuid)->first();
    }


    /**
     * Update by UUID
     */
    public function updateByUuid(string $uuid, array $data): CallRegister
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $data['updated_user'] = Auth::id();

            $record->fill($data);
            $record->save();

            DB::commit();

            return $record;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("CallRegister update failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $data
            ]);

            throw new \Exception("Something went wrong while updating Call Register", 0, $e);
        }
    }


    /**
     * Delete by UUID (soft delete)
     */
    public function deleteByUuid(string $uuid): bool
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $record->deleted_user = Auth::id();
            $record->save();

            $record->delete();

            DB::commit();

            return true;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("CallRegister delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting Call Register", 0, $e);
        }
    }


    /**
     * Global Search (multiple columns)
     */
    public function globalSearch(string $searchTerm = null, int $perPage = 20)
    {
        try {
            $query = CallRegister::query();

            if (!empty($searchTerm)) {

                $searchTerm = strtolower($searchTerm);
                $like      = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($like) {
                    $q->orWhereRaw("LOWER(ticket_no) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(outlet_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(owner_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(model_number) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(osa_code) LIKE ?", [$like]);
                });
            }

            $query->orderBy('created_at', 'desc');

            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("CallRegister global search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm
            ]);

            throw new \Exception("Failed to search Call Register", 0, $e);
        }
    }


    public function getChillerBySerial(string $serial)
    {
        return AddChiller::select([
            'id',
            'serial_number',
            'osa_code',
            'assets_category',
            'model_number',
            'manufacturer',
            'branding',
            'country_id',
            'customer_id',
        ])
            ->with([
                'assetsCategory:id,osa_code,name',
                'modelNumber:id,code,name',
                'manufacture:id,osa_code,name',
                'brand:id,osa_code,name',
                'country:id,country_code,country_name',
                'customer:id,osa_code,name,owner_name,street,landmark,town,district,contact_no,contact_no2'
            ])
            ->where('serial_number', $serial)
            ->first();
    }
}
