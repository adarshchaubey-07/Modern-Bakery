<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ChillerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Models\User;

class ChillerRequestService
{
    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     try {
    //         $user = auth()->user();

    //         if ($dropdown) {
    //             $query = ChillerRequest::select(['id', 'asset_code', 'asset_name'])
    //                 ->orderBy('asset_name', 'asc');

    //             foreach ($filters as $field => $value) {
    //                 if (empty($value)) continue;

    //                 if (in_array($field, ['asset_code', 'asset_name'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } elseif ($field === 'warehouse_id') {
    //                     $query->where('warehouse_id', $value);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //             return $query->get();
    //         }

    //         $query = ChillerRequest::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username'
    //         ]);

    //         $query = \App\Helpers\DataAccessHelper::filterAssets($query, $user);

    //         foreach ($filters as $field => $value) {
    //             if (empty($value)) continue;

    //             if (in_array($field, ['asset_code', 'asset_name'])) {
    //                 $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             } elseif ($field === 'warehouse_id') {
    //                 $query->where('warehouse_id', $value);
    //             } else {
    //                 $query->where($field, $value);
    //             }
    //         }

    //         $query->orderBy('created_at', 'desc');

    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch assets: " . $e->getMessage());
    //     }
    // }
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $user = auth()->user();

            if ($dropdown) {
                $query = ChillerRequest::select(['id', 'asset_code', 'asset_name'])
                    ->orderBy('asset_name', 'asc');

                foreach ($filters as $field => $value) {
                    if (empty($value)) continue;

                    if (in_array($field, ['asset_code', 'asset_name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } elseif ($field === 'warehouse_id') {
                        $query->where('warehouse_id', $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
                return $query->get();
            }
            $query = ChillerRequest::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username'
            ]);
            $query = \App\Helpers\DataAccessHelper::filterAssets($query, $user);
            foreach ($filters as $field => $value) {
                if (empty($value)) continue;
                if (in_array($field, ['asset_code', 'asset_name'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif ($field === 'warehouse_id') {
                    $query->where('warehouse_id', $value);
                } else {
                    $query->where($field, $value);
                }
            }
            $query->orderBy('created_at', 'desc');
            $result = $query->paginate($perPage);
            $result->getCollection()->transform(function ($item) {
                $workflowRequest = \DB::table('htapp_workflow_requests')
                    ->where('process_type', 'Chiller_Request')
                    ->where('process_id', $item->id)
                    ->orderBy('id', 'DESC')
                    ->first();
                $item->approval_status = $workflowRequest->status ?? 'NO_WORKFLOW';
                $item->workflow_request_uuid = $workflowRequest->uuid ?? null;
                return $item;
            });
            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch assets: " . $e->getMessage());
        }
    }

    public function generateCode(): string
    {
        do {
            $last = ChillerRequest::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'CR' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ChillerRequest::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    // public function create(array $data): ChillerRequest
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data = array_merge($data, [
    //             'osa_code' => $this->generateCode(),
    //             'uuid'        => $data['uuid'] ?? Str::uuid()->toString(),
    //         ]);
    //         $fileColumns = [
    //             'password_photo_file',
    //             'lc_letter_file',
    //             'trading_licence_file',
    //             'outlet_stamp_file',
    //             'outlet_address_proof_file',
    //             'sign__customer_file',
    //             'national_id_file'
    //         ];

    //         foreach ($fileColumns as $col) {
    //             if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
    //                 $filename = time() . '_' . $data[$col]->getClientOriginalName();
    //                 $data[$col]->storeAs('public/chillers', $filename);
    //                 $data[$col] = $filename;
    //             }
    //         }
    //         $chiller = ChillerRequest::create($data);
    //         DB::commit();
    //         return $chiller;
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error("Chiller Request creation failed", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'data'  => $data,
    //             'user'  => Auth::id(),
    //         ]);

    //         throw new \Exception("Something went wrong, please try again.", 0, $e);
    //     }
    // }
    public function create(array $data): ChillerRequest
    {
        DB::beginTransaction();
        try {

            $data = array_merge($data, [
                'osa_code' => $this->generateCode(),
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
            ]);

            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];

            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
                    $filename = time() . '_' . $data[$col]->getClientOriginalName();
                    $data[$col]->storeAs('public/chillers', $filename);
                    $data[$col] = $filename;
                }
            }

            $chiller = ChillerRequest::create($data);

            DB::commit();
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Chiller_Request')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        "workflow_id"  => $workflow->workflow_id,
                        "process_type" => "Chiller_Request",
                        "process_id"   => $chiller->id
                    ]);
            }

            return $chiller;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("Chiller Request creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }
    public function findByUuid(string $uuid): ?ChillerRequest
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        return ChillerRequest::where('uuid', $uuid)->first();
    }

    public function updateByUuid(string $uuid, array $data): chillerRequest
    {
        $chiller = $this->findByUuid($uuid);

        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];

            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {

                    $filename = Str::random(40) . '.' . $data[$col]->getClientOriginalExtension();

                    $folder = "chiller_requests";

                    $relativePath = $folder . '/' . $filename;

                    $data[$col]->storeAs($folder, $filename, 'public');
                    $appUrl = rtrim(config('app.url'), '/');
                    $data[$col] = $appUrl . '/storage/app/public/' . $relativePath;
                }
            }


            $chiller->fill($data);
            $chiller->save();

            DB::commit();

            return $chiller;
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error("Chiller update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }


    public function deleteByUuid(string $uuid): void
    {
        $chillerRequest = $this->findByUuid($uuid);
        if (!$chillerRequest) {
            throw new \Exception("ChillerRequest not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $chillerRequest->deleted_user = Auth::id();
            $chillerRequest->save();
            $chillerRequest->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("ChillerRequest delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting Chiller Request: " . $e->getMessage(), 0, $e);
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = ChillerRequest::with([
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'agent:id,customer_code,business_name',
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(owner_name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(outlet_name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(outlet_type) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(asset_number) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("ChillerRequest search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search Chiller Requests: " . $e->getMessage(), 0, $e);
        }
    }



    public function filterChillerRequests(array $filters)
    {
        $query = ChillerRequest::query();

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['model_number'])) {
            $query->where('model_number', $filters['model_number']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        // 🔥 EXCLUDE ALL CHILLERS WHERE status = 5
        $query->where('status', '!=', 5);

        $query->whereIn('id', function ($q) {
            $q->select('process_id')
                ->from('htapp_workflow_requests')
                ->where('process_type', 'Approved_CRF_Request')
                ->whereIn('id', function ($q2) {
                    $q2->select('workflow_request_id')
                        ->from('htapp_workflow_request_steps')
                        ->whereIn('status', ['APPROVED', 'PENDING']);
                });
        });

        $paginated = $query->paginate(50);

        $chillerRequestIds = $paginated->pluck('id')->toArray();

        if (empty($chillerRequestIds)) {
            return $paginated;
        }

        $workflowRequests = DB::table('htapp_workflow_requests')
            ->where('process_type', 'Approved_CRF_Request')
            ->whereIn('process_id', $chillerRequestIds)
            ->pluck('id', 'process_id');

        $workflowIds = $workflowRequests->values();

        $approvedSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowIds)
            ->where('status', 'APPROVED')
            ->get()
            ->groupBy('workflow_request_id');

        $pendingSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowIds)
            ->where('status', 'PENDING')
            ->get()
            ->groupBy('workflow_request_id');

        $paginated->getCollection()->transform(function ($item) use ($workflowRequests, $approvedSteps, $pendingSteps) {
            $workflowId = $workflowRequests[$item->id] ?? null;

            return [
                'chiller_request'     => $item,
                'workflow_request_id' => $workflowId,
                'approved_steps'      => $approvedSteps[$workflowId] ?? [],
                'pending_steps'       => $pendingSteps[$workflowId] ?? [],
            ];
        });

        return $paginated;
    }





    /**
     * Fetch ONLY approved chiller requests
     */
    public function getApprovedChillerRequests(array $filters)
    {
        // 1. Filter users
        $usersQuery = User::query();

        if (!empty($filters['region'])) {
            $usersQuery->whereJsonContains('region', (int)$filters['region']);
        }

        if (!empty($filters['area'])) {
            $usersQuery->whereJsonContains('area', (int)$filters['area']);
        }

        if (!empty($filters['warehouse'])) {
            $usersQuery->whereJsonContains('warehouse', (int)$filters['warehouse']);
        }

        $userIds = $usersQuery->pluck('id')->toArray();

        if (empty($userIds)) {
            return [];
        }

        // 2. Chiller Requests by these users
        $chillerRequestIds = ChillerRequest::whereIn('created_user', $userIds)
            ->orWhereIn('updated_user', $userIds)
            ->pluck('id')
            ->toArray();

        if (empty($chillerRequestIds)) {
            return [];
        }

        // 3. Workflow request IDs for these chillers
        $workflowRequests = DB::table('htapp_workflow_requests')
            ->where('process_type', 'CHILLER_REQUEST')
            ->whereIn('process_id', $chillerRequestIds)
            ->pluck('id', 'process_id'); // key = chiller_id

        if ($workflowRequests->isEmpty()) {
            return [];
        }

        // 4. Get APPROVED workflow steps
        $approvedSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowRequests->values())
            ->where('status', 'APPROVED')
            ->get()
            ->groupBy('workflow_request_id');

        if ($approvedSteps->isEmpty()) {
            return [];
        }

        // 5. Build final response
        $result = [];

        foreach ($workflowRequests as $chillerId => $workflowRequestId) {

            // Skip non-approved
            if (!isset($approvedSteps[$workflowRequestId])) {
                continue;
            }

            $result[] = [
                'chiller_request'     => ChillerRequest::find($chillerId),
                'workflow_request_id' => $workflowRequestId,
                'steps'               => $approvedSteps[$workflowRequestId],
            ];
        }

        return $result;
    }

    public function exportCRFRequests(
        $status = null,
        $region_id = null,
        $user_id = null,
        $warehouse_id = null,
        $route_id = null,
        $salesman_id = null,
        $model_id = null
    ) {
        $region_id     = $region_id     ? explode(',', $region_id)     : [];
        $user_id       = $user_id       ? explode(',', $user_id)       : [];
        $warehouse_id  = $warehouse_id  ? explode(',', $warehouse_id)  : [];
        $route_id      = $route_id      ? explode(',', $route_id)      : [];
        $salesman_id   = $salesman_id   ? explode(',', $salesman_id)   : [];
        $model_id      = $model_id      ? explode(',', $model_id)      : [];

        $q = DB::table('chiller_requests AS ch')
            ->select([
                'iro.status AS status_iro',
                'ch.id AS crf_id',

                'ac.name AS customer_name',
                'ac.osa_code AS customer_code',
                'ac.street AS city',
                'ac.district',
                'ac.contact_no AS phone1',
                'ac.contact_no2 AS phone2',

                'fr.osa_code AS fridge_code',
                'fr.serial_number',
                'fr.model_number',
                'fr.branding AS type',

                'wh.warehouse_name',
                'wh.warehouse_code',

                'ar.area_code AS region_code',
                'ar.area_name AS region_name',

                'rt.route_code',
                'rt.route_name',

                'sm.osa_code AS salesman_code',
                'sm.name AS salesman_name',

                'ch.created_at'
            ])

            ->leftJoin('tbl_iro_headers AS iro', 'iro.id', '=', 'ch.iro_id')
            ->leftJoin('agent_customers AS ac', 'ac.id', '=', 'ch.customer_id')
            ->leftJoin('tbl_add_chillers AS fr', 'fr.id', '=', 'ac.fridge_id')
            ->leftJoin('tbl_warehouse AS wh', 'wh.id', '=', 'ch.warehouse_id')
            ->leftJoin('tbl_areas AS ar', 'ar.id', '=', 'wh.area_id')
            ->leftJoin('tbl_route AS rt', 'rt.id', '=', 'ac.route_id')
            ->leftJoin('salesman AS sm', 'sm.id', '=', 'ch.salesman_id')

            /** JSON SAFE JOIN FOR USERS */
            ->leftJoin('users AS u', function ($j) {
                $j->on(DB::raw("
                CASE WHEN jsonb_typeof(u.area::jsonb) = 'array'
                        THEN ((u.area::jsonb)->>0)::int
                     ELSE (u.area::text)::int
                END
            "), '=', DB::raw("wh.area_id"))
                    ->whereIn('u.role', [1])
                    ->where('u.status', 1);
            });


        /** STATUS FILTER */
        if ($status !== null) {
            $q->where('ch.status', (int)$status);
        }

        if (!empty($warehouse_id)) {
            $q->whereIn('ch.warehouse_id', $warehouse_id);
        }
        if (!empty($region_id)) {
            $q->whereIn('wh.region_id', $region_id);
        }
        if (!empty($route_id)) {
            $q->whereIn('ac.route_id', $route_id);
        }
        if (!empty($salesman_id)) {
            $q->whereIn('ch.salesman_id', $salesman_id);
        }
        if (!empty($user_id)) {
            $q->whereIn('u.id', $user_id);
        }

        /** MODEL FILTER */
        if (!empty($model_id)) {
            $sizes = DB::table('am_model_number')
                ->whereIn('id', $model_id)
                ->pluck('size')
                ->toArray();
            if (!empty($sizes)) {
                $q->whereIn('ch.model_number', $sizes);
            }
        }

        $q->orderBy('ch.id', 'DESC');

        return $q->get();
    }
}
