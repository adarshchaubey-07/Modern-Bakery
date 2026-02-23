<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ServiceVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Helpers\ApprovalHelper; 
use Throwable;

class ServiceVisitService
{
// use App\Helpers\ApprovalHelper;

public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
{
    try {
        if ($dropdown) {
            return ServiceVisit::select('id', 'uuid', 'osa_code', 'ticket_type')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $query = ServiceVisit::query();

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

        $result = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $result->getCollection()->transform(function ($item) {
            return ApprovalHelper::attach($item, 'Service_Visit');
        });

        return $result;

    } catch (\Exception $e) {
        throw new \Exception(
            "Failed to fetch service visits: " . $e->getMessage()
        );
    }
}

public function generateCode(string $prefix = 'SV'): string
    {
        do {
            $last = ServiceVisit::withTrashed()
                ->where('osa_code', 'like', $prefix . '%')
                ->latest('id')
                ->first();
            if ($last && $last->osa_code) {
                $number = (int) preg_replace('/\D/', '', $last->osa_code);
                $next   = $number + 1;
            } else {
                $next = 1;
            }
            $osa_code = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        } while (
            ServiceVisit::withTrashed()
            ->where('osa_code', $osa_code)
            ->exists()
        );

        return $osa_code;
    }

// public function create(array $data): ServiceVisit
// {
//     return DB::transaction(function () use ($data) {
//         if (!empty($data['osa_code'])) {
//             $osaCode = strtoupper($data['osa_code']);
//         } else {
//             $prefix  = $data['prefix'] ?? 'BD';
//             $osaCode = $this->generateCode($prefix);
//         }
//         $data = array_merge($data, [
//             'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
//             'osa_code'     => $osaCode,
//             'created_user' => Auth::id(),
//         ]);
//         unset($data['prefix']);
//         $appUrl = rtrim(config('app.url'), '/');
//         $fileColumns = [
//             'scan_image',
//             'is_machine_in_working_img',
//             'cleanliness_img',
//             'condensor_coil_cleand_img',
//             'gaskets_img',
//             'light_working_img',
//             'branding_no_img',
//             'propper_ventilation_available_img',
//             'leveling_positioning_img',
//             'stock_availability_in_img',
//             'cooler_image',
//             'cooler_image2',
//             'type_details_photo1',
//             'type_details_photo2',
//             'customer_signature',
//         ];
//         foreach ($fileColumns as $column) {
//             if (
//                 isset($data[$column]) &&
//                 $data[$column] instanceof \Illuminate\Http\UploadedFile &&
//                 $data[$column]->isValid()
//             ) {
//                 $filename = Str::random(20) . '.' . $data[$column]->getClientOriginalExtension();
//                 $data[$column]->storeAs('service_visit', $filename, 'public');
//                 $data[$column] = $appUrl . '/storage/app/public/service_visit/' . $filename;
//             } else {
//                 unset($data[$column]);
//             }
//         }
//         return ServiceVisit::create($data);
//     });
// }

public function create(array $data): ServiceVisit
{
    return DB::transaction(function () use ($data) {

        if (!empty($data['osa_code'])) {
            $osaCode = strtoupper($data['osa_code']);
        } else {
            $prefix  = $data['prefix'] ?? 'BD';
            $osaCode = $this->generateCode($prefix);
        }

        $data = array_merge($data, [
            'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
            'osa_code'     => $osaCode,
            'created_user' => Auth::id(),
        ]);

        unset($data['prefix']);

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

        $visit = ServiceVisit::create($data);
        $assignment = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Service_Visit')
            ->where('is_active', true)
            ->first();
        
        if ($assignment) {
            app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'process_type' => 'Service_Visit',
                    'process_id'   => $visit->id,
                ]);
        }

        return $visit;
    });
}


    /**
     * Find record by UUID
     */
    // public function findByUuid(string $uuid): ?ServiceVisit
    // {
    //     if (!Str::isUuid($uuid)) {
    //         throw new \Exception("Invalid UUID format: {$uuid}");
    //     }
    //     $serviceVisit=ServiceVisit::where('uuid',$uuid)->first();
    //     if(!$serviceVisit){
    //         return null;
    //     }

    //     return ServiceVisit::where('uuid', $uuid)->first();
    // }

    public function findByUuid(string $uuid): ?ServiceVisit
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }
        $serviceVisit=ServiceVisit::where('uuid',$uuid)->first();
        if(!$serviceVisit){
            return null;
        }
        return ApprovalHelper::attach($serviceVisit, 'Service_Visit');
    }

    /**
     * Update record by UUID
     */
    // public function updateByUuid(string $uuid, array $data): ServiceVisit
    // {
    //     $record = $this->findByUuid($uuid);

    //     if (!$record) {
    //         throw new \Exception("Record not found or invalid UUID: {$uuid}");
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $fileColumns = [
    //             'scan_image',
    //             'cooler_image',
    //             'cooler_image2',
    //             'type_details_photo1',
    //             'type_details_photo2',
    //             'customer_signature',
    //         ];

    //         foreach ($fileColumns as $col) {
    //             if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {

    //                 $filename = Str::random(40) . '.' . $data[$col]->getClientOriginalExtension();

    //                 $folder = "service_visit";

    //                 $data[$col]->storeAs($folder, $filename, 'public');

    //                 $appUrl = rtrim(config('app.url'), '/');
    //                 $data[$col] = $appUrl . '/storage/app/public/' . $folder . '/' . $filename;
    //             }
    //         }

    //         $data['updated_user'] = Auth::id();

    //         $record->fill($data);
    //         $record->save();

    //         DB::commit();
    //         return $record;
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         Log::error("Service Visit update failed", [
    //             'error'   => $e->getMessage(),
    //             'uuid'    => $uuid,
    //             'payload' => $data,
    //         ]);

    //         throw new \Exception("Something went wrong, please try again.", 0, $e);
    //     }
    // }


    public function updateByUuid(string $uuid, array $data): ServiceVisit
    {
        $record = $this->findByUuid($uuid);

        if (! $record) {
            throw new \Exception("Record not found or invalid UUID: {$uuid}");
        }

        return DB::transaction(function () use ($record, $data) {

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

            // 🔹 Handle file uploads exactly like create()
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
                    // 🔴 CRITICAL: do NOT overwrite existing value
                    unset($data[$column]);
                }
            }

            // 🔹 Track updater
            $data['updated_user'] = Auth::id();

            // 🔹 Update ONLY provided fields
            $record->fill($data);
            $record->save();

            return $record;
        });
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


    public function export(array $filters)
    {
        $query = DB::table('tbl_service_visit')
            ->select([
                'osa_code',
                'ticket_type',
                'time_in',
                'time_out',

                'outlet_code',
                'outlet_name',
                'owner_name',
                'contact_no',

                'district',
                'town_village',
                'location',

                'model_no',
                'asset_no',
                'serial_no',
                'branding',

                DB::raw("CASE WHEN is_machine_in_working = 1 THEN 'Yes' ELSE 'No' END"),
                DB::raw("CASE WHEN cleanliness = 1 THEN 'Yes' ELSE 'No' END"),
                DB::raw("CASE WHEN condensor_coil_cleand = 1 THEN 'Yes' ELSE 'No' END"),
                DB::raw("CASE WHEN gaskets = 1 THEN 'Yes' ELSE 'No' END"),
                DB::raw("CASE WHEN light_working = 1 THEN 'Yes' ELSE 'No' END"),

                'work_status',
                'complaint_type',
                'comment',

                'technician_id',
                DB::raw("created_at::date"),
            ])
            ->whereNull('deleted_at');

        // 🔹 Filters
        if (!empty($filters['technician_id'])) {
            $query->where('technician_id', $filters['technician_id']);
        }

        if (!empty($filters['work_status'])) {
            $query->where('work_status', $filters['work_status']);
        }

        if (!empty($filters['ticket_type'])) {
            $query->where('ticket_type', $filters['ticket_type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }
}
