<?php

namespace App\Services\V1\Assets\Web;

use App\Models\FrigeCustomerUpdate;
use App\Exports\FridgeCustomerUpdateExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class FrigeCustomerUpdateService
{

    // public function list(array $filters): LengthAwarePaginator
    // {
    //     $query = FrigeCustomerUpdate::query()
    //         ->orderByDesc('id');
    //     if (!empty($filters['search'])) {
    //         $search = $filters['search'];
    //         $query->where(function ($q) use ($search) {
    //             $q->where('osa_code', 'ILIKE', "%{$search}%")
    //                 ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('owner_name', 'ILIKE', "%{$search}%");
    //         });
    //     }
    //     if (!empty($filters['osa_code'])) {
    //         $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
    //     }
    //     if (isset($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }
    //     if (!empty($filters['salesman_id'])) {
    //         $query->where('salesman_id', $filters['salesman_id']);
    //     }
    //     if (!empty($filters['route_id'])) {
    //         $query->where('route_id', $filters['route_id']);
    //     }
    //     $limit = (int) ($filters['limit'] ?? 20);
    //     return $query->paginate($limit);
    // }
    public function list(array $filters): LengthAwarePaginator
    {
        $query = FrigeCustomerUpdate::query()
            ->orderByDesc('id');

        if (!empty($filters) && isset($filters['filter']) && is_array($filters['filter'])) {

            $warehouseIds = \App\Helpers\CommonLocationFilter::resolveWarehouseIds([
                'company'   => $filters['filter']['company_id']   ?? null,
                'region'    => $filters['filter']['region_id']    ?? null,
                'area'      => $filters['filter']['area_id']      ?? null,
                'warehouse' => $filters['filter']['warehouse_id'] ?? null,
                'route'     => $filters['filter']['route_id']     ?? null,
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
                    ->orWhere('owner_name', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        $limit = (int) ($filters['limit'] ?? 20);
        $result = $query->paginate($limit);

        /**
         * ======================================================
         * 🔥 Inject Approval Status (OLD FLAT FORMAT)
         * ======================================================
         */
        $result->getCollection()->transform(function ($item) {

            $workflowRequest = \DB::table('htapp_workflow_requests')
                ->where('process_type', 'Frige_Customer_Update')
                ->where('process_id', $item->id)
                ->latest()
                ->first();

            $item->approval_status = null;
            $item->current_step    = null;
            $item->request_step_id = null;
            $item->progress        = null;

            if ($workflowRequest) {

                $currentStep = \DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                    ->orderBy('step_order')
                    ->first();

                $totalSteps = \DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->count();

                $approvedSteps = \DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->count();

                $lastApprovedStep = \DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->orderBy('step_order', 'desc')
                    ->first();

                $item->approval_status = $lastApprovedStep
                    ? $lastApprovedStep->message
                    : 'Initiated';

                $item->current_step    = $currentStep->title ?? null;
                $item->request_step_id = $currentStep->id ?? null;
                $item->progress        = $totalSteps > 0
                    ? "{$approvedSteps}/{$totalSteps}"
                    : null;
            }

            return $item;
        });

        return $result;
    }

    // public function getByUuid(string $uuid): FrigeCustomerUpdate
    // {
    //     return FrigeCustomerUpdate::where('uuid', $uuid)->firstOrFail();
    // }
    public function getByUuid(string $uuid): FrigeCustomerUpdate
    {
        if (!\Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        $asset = FrigeCustomerUpdate::where('uuid', $uuid)->firstOrFail();

        /**
         * =====================================================
         * 🔥 APPROVAL (OLD FLAT FORMAT)
         * =====================================================
         */
        $workflowRequest = \DB::table('htapp_workflow_requests')
            ->where('process_type', 'Frige_Customer_Update')
            ->where('process_id', $asset->id)
            ->latest()
            ->first();

        $asset->approval_status = null;
        $asset->current_step    = null;
        $asset->request_step_id = null;
        $asset->progress        = null;

        if ($workflowRequest) {

            $currentStep = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->count();

            $approvedSteps = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'desc')
                ->first();

            $asset->approval_status = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $asset->current_step    = $currentStep->title ?? null;
            $asset->request_step_id = $currentStep->id ?? null;
            $asset->progress        = $totalSteps > 0
                ? "{$approvedSteps}/{$totalSteps}"
                : null;
        }

        return $asset;
    }

    public function updateByUuid(string $uuid, array $data): FrigeCustomerUpdate
    {
        $record = FrigeCustomerUpdate::where('uuid', $uuid)->first();

        if (! $record) {
            throw new ModelNotFoundException('Fridge customer update not found');
        }

        foreach ($data as $key => $value) {
            $record->{$key} = $value;
        }

        $record->save();

        return $record->refresh();
    }

    public function export(Request $request): array
    {

        $format = strtolower($request->input('format', 'xlsx'));

        if (!in_array($format, ['csv', 'xlsx'])) {
            throw new \Exception('Invalid format. Use csv or xlsx only.');
        }

        $filename = 'fridge_customer_update_' . now()->format('Ymd_His') . '.' . $format;
        $path     = 'exports/' . $filename;
        $query = FrigeCustomerUpdate::with([
            'salesman:id,osa_code,name',
            'route:id,route_code,route_name',
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,osa_code,name'
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
                    ->orWhere('owner_name', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_number', 'ILIKE', "%{$search}%")
                    ->orWhere('asset_number', 'ILIKE', "%{$search}%")
                    ->orWhere('serial_no', 'ILIKE', "%{$search}%")
                    ->orWhere('brand', 'ILIKE', "%{$search}%")

                    ->orWhereHas('salesman', function ($s) use ($search) {
                        $s->where('osa_code', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%");
                    })

                    ->orWhereHas('route', function ($r) use ($search) {
                        $r->where('route_code', 'ILIKE', "%{$search}%")
                            ->orWhere('route_name', 'ILIKE', "%{$search}%");
                    })

                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('osa_code', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        /* ========= DATE FILTER ========= */
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date,
                $request->to_date
            ]);
        }

        $export = new FridgeCustomerUpdateExport($query);

        Excel::store(
            $export,
            $path,
            'public',
            $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );

        $appUrl = rtrim(config('app.url'), '/');

        return [
            'download_url' => $appUrl . '/storage/app/public/' . $path
        ];
    }

    public function globalSearch(string $searchTerm = null, int $perPage = 20)
    {
        try {
            $query = FrigeCustomerUpdate::query()
                ->with([
                    'salesman:id,osa_code,name',
                    'route:id,route_code,route_name',
                    'warehouse:id,warehouse_code,warehouse_name',
                    'customer:id,osa_code,name'
                ]);

            if (!is_null($searchTerm) && trim($searchTerm) !== '') {

                $searchTerm = strtolower(trim($searchTerm));
                $like = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($like) {

                    /* OWN TABLE */
                    $q->orWhereRaw('LOWER(osa_code) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(outlet_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(owner_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(contact_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(asset_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(serial_no) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(brand) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(model) LIKE ?', [$like]);

                    /* SALESMAN */
                    $q->orWhereHas('salesman', function ($s) use ($like) {
                        $s->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    /* ROUTE */
                    $q->orWhereHas('route', function ($r) use ($like) {
                        $r->whereRaw('LOWER(route_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(route_name) LIKE ?', [$like]);
                    });

                    /* CUSTOMER */
                    $q->orWhereHas('customer', function ($c) use ($like) {
                        $c->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    /* WAREHOUSE */
                    $q->orWhereHas('warehouse', function ($w) use ($like) {
                        $w->whereRaw('LOWER(warehouse_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(warehouse_name) LIKE ?', [$like]);
                    });
                });
            }

            $query->orderBy('id', 'desc');

            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            // dd($e);
            Log::error('FridgeCustomerUpdate global search failed', [
                'error'  => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception('Failed to search Fridge Customer Update', 0, $e);
        }
    }
}
