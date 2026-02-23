<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ServiceVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class ServiceVisitService
{
    /**
     * Generate OSA code
     * Example: SV001, SV002, ...
     */
    public function generateCode(): string
    {
        do {
            $last = ServiceVisit::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'SV' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ServiceVisit::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }


    /**
     * Get paginated list with filtering
     */
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            // ✅ Dropdown mode (no pagination)
            if ($dropdown) {
                return ServiceVisit::select('id', 'uuid', 'osa_code', 'ticket_type')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $query = ServiceVisit::query();

            // ✅ Apply filters safely
            foreach ($filters as $field => $value) {

                if ($value === null || $value === '') {
                    continue;
                }

                if (in_array($field, ['ticket_type', 'outlet_name', 'owner_name'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }

            return $query
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception(
                "Failed to fetch service visits: " . $e->getMessage()
            );
        }
    }

    /**
     * Create new record
     */
    public function create(array $data): ServiceVisit
    {
        return DB::transaction(function () use ($data) {

            $data = array_merge($data, [
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code'     => $this->generateCode(),
                'created_user' => Auth::id(),
            ]);

            $appUrl = rtrim(config('app.url'), '/');

            $fileColumns = [
                'scan_image',
                'is_machine_in_working_img',
                'cleanliness_img',
                'condensor_coil_cleand_img',
                'gaskets_img',
                'light_working_img',
                'branding_no_img',
                'propper_ventilation_available_img',
                'leveling_positioning_img',
                'stock_availability_in_img',
                'cooler_image',
                'cooler_image2',
                'type_details_photo1',
                'type_details_photo2',
                'customer_signature',
            ];

            foreach ($fileColumns as $column) {

                if (
                    isset($data[$column]) &&
                    $data[$column] instanceof \Illuminate\Http\UploadedFile &&
                    $data[$column]->isValid()
                ) {
                    $filename = Str::random(20) . '.' . $data[$column]->getClientOriginalExtension();

                    $data[$column]->storeAs('service_visit', $filename, 'public');

                    $data[$column] = $appUrl . '/storage/app/public/service_visit/' . $filename;
                } else {
                    unset($data[$column]);
                }
            }

            return ServiceVisit::create($data);
        });
    }



    /**
     * Find record by UUID
     */
    public function findByUuid(string $uuid): ?ServiceVisit
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        return ServiceVisit::where('uuid', $uuid)->first();
    }


    /**
     * Update record by UUID
     */
    public function updateByUuid(string $uuid, array $data): ServiceVisit
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Record not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {

            $fileColumns = [
                'scan_image',
                'cooler_image',
                'cooler_image2',
                'type_details_photo1',
                'type_details_photo2',
                'customer_signature',
            ];

            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {

                    $filename = Str::random(40) . '.' . $data[$col]->getClientOriginalExtension();

                    $folder = "service_visit";

                    $data[$col]->storeAs($folder, $filename, 'public');

                    $appUrl = rtrim(config('app.url'), '/');
                    $data[$col] = $appUrl . '/storage/app/public/' . $folder . '/' . $filename;
                }
            }

            $data['updated_user'] = Auth::id();

            $record->fill($data);
            $record->save();

            DB::commit();
            return $record;
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error("Service Visit update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }


    /**
     * Delete record by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Service Visit not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $record->deleted_user = Auth::id();
            $record->save();
            $record->delete();

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("ServiceVisit delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting service visit: " . $e->getMessage(), 0, $e);
        }
    }
}
