<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\LoadDetail;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\WarehouseStock;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\DataAccessHelper;

class LoadHeaderService
{

    // public function store(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         // 🔹 Handle nested payloads like ["data" => [...]]
    //         if (isset($data['data']) && is_array($data['data'])) {
    //             $data = $data['data'];
    //         }

    //         $osaCodeHeader = $this->generateOsaCode('SLH');

    //         // ✅ Create header as Eloquent model
    //         $header = LoadHeader::query()->create([
    //             'uuid' => Str::uuid(),
    //             'osa_code' => $osaCodeHeader,
    //             'salesman_id' => $data['salesman_id'],
    //             'route_id' => $data['route_id'],
    //             'warehouse_id' => $data['warehouse_id'] ?? null,
    //             'load_date' => $data['load_date'] ?? null,
    //             'remarks' => $data['remarks'] ?? null,
    //             'latitude' => $data['latitude'] ?? null,
    //             'longtitude' => $data['longtitude'] ?? null,
    //             'salesman_type' => $data['salesman_type'] ?? null,
    //             'project_type' => $data['project_type'] ?? null,
    //             'sync_time' => $data['sync_time'] ?? null,
    //             'status' => $data['status'] ?? 1,
    //         ]);

    //         if (!($header instanceof \App\Models\Agent_Transaction\LoadHeader)) {
    //             throw new \Exception('Header creation failed — not a model instance.');
    //         }

    //         // 🔹 Create details
    //         foreach ($data['details'] as $detail) {
    //             $osaCodeDetail = $this->generateOsaCode('SLD');
    //             LoadDetail::create([
    //                 'uuid' => Str::uuid(),
    //                 'osa_code' => $osaCodeDetail,
    //                 'header_id' => $header->id,
    //                 'item_id' => $detail['item_id'],
    //                 'uom' => $detail['uom'],
    //                 'qty' => $detail['qty'],
    //                 'price' => $detail['price'] ?? 0,
    //                 'status' => $detail['status'] ?? 1,
    //             ]);
    //         }

    //         DB::commit();
    //         return $header->load(['details', 'warehouse', 'route', 'salesman', 'projecttype']);
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Load creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'status' => 'error',
    //             'code' => 500,
    //             'message' => 'Load creation failed: ' . $e->getMessage(),
    //         ];
    //     }
    // }


// public function store(array $data)
//     {
//         DB::beginTransaction();
//         try {
//             if (isset($data['data']) && is_array($data['data'])) {
//                 $data = $data['data'];
//             }
//             $osaCodeHeader = $this->generateOsaCode('SLH');
//             $header = LoadHeader::create([
//                 'uuid'          => Str::uuid(),
//                 'osa_code'      => $osaCodeHeader,
//                 'salesman_id'   => $data['salesman_id'] ?? null,
//                 'route_id'      => $data['route_id'] ?? null,
//                 'warehouse_id'  => $data['warehouse_id'] ?? null,
//                 'load_date'     => $data['load_date'] ?? null,
//                 'remarks'       => $data['remarks'] ?? null,
//                 'latitude'      => $data['latitude'] ?? null,
//                 'longtitude'    => $data['longtitude'] ?? null,
//                 'salesman_type' => $data['salesman_type'] ?? null,
//                 'project_type'  => $data['project_type'] ?? null,
//                 'sync_time'     => $data['sync_time'] ?? null,
//                 'status'        => $data['status'] ?? 1,
//             ]);
//             if (!$header instanceof LoadHeader) {
//                 throw new \Exception('Failed to create header');
//             }
//             $warehouseId = $data['warehouse_id'];
//             foreach ($data['details'] as $detail) {
//                 $itemId = $detail['item_id'];
//                 $sentQty = (float)$detail['qty'];
//                 $uomId = $detail['uom'];
//                 $item = Item::find($itemId);
//                 $itemName = $item ? $item->name : "Item ID: {$itemId}";
//                 $itemUom = DB::table('item_uoms')
//                     ->where('item_id', $itemId)
//                     ->where('uom_id', $uomId)
//                     ->first();
//                 if (!$itemUom) {
//                     DB::rollBack();
//                     return [
//                         'status' => 'error',
//                         'message' => "UOM not found for {$itemName} (UOM ID: {$uomId})"
//                     ];
//                 }
//                 if (!$itemUom->upc) {
//                     DB::rollBack();
//                     return [
//                         'status' => 'error',
//                         'message' => "UPC missing for {$itemName} (UOM ID: {$uomId})"
//                     ];
//                 }
//                 $baseQty = $sentQty * (float)$itemUom->upc;
//                 $stock = WarehouseStock::where('warehouse_id', $warehouseId)
//                     ->where('item_id', $itemId)
//                     ->first();
//                 if (!$stock) {
//                     DB::rollBack();
//                     return [
//                         'status' => 'error',
//                         'message' => "Stock not found for {$itemName} in warehouse {$warehouseId}"
//                     ];
//                 }
//                 if ($stock->qty < $baseQty) {
//                     DB::rollBack();
//                     return [
//                         'status' => 'error',
//                         'message' => "Insufficient stock for {$itemName}. Available: {$stock->qty}, Required: {$baseQty}"
//                     ];
//                 }
//                 $stock->update([
//                     'qty' => $stock->qty - $baseQty,
//                     'updated_user' => $data['created_user'] ?? null
//                 ]);
//                 LoadDetail::create([
//                     'uuid'      => Str::uuid(),
//                     'osa_code'  => $this->generateOsaCode('SLD'),
//                     'header_id' => $header->id,
//                     'item_id'   => $itemId,
//                     'uom'       => $uomId,     
//                     'qty'       => $sentQty,   
//                     'price'     => $detail['price'] ?? 0,
//                     'status'    => $detail['status'] ?? 1,
//                     'unload_status'    => $detail['unload_status'] ?? 0,
//                 ]);
//             }
//             DB::commit();
//             return $header->load([
//                 'details',
//                 'warehouse',
//                 'route',
//                 'salesman',
//                 'projecttype',
//                 'salesmantype',
//             ]);
//         } catch (\Throwable $e) {
//             DB::rollBack();
//             return [
//                 'status' => 'error',
//                 'message' => $e->getMessage()
//             ];
//         }
//     }
public function store(array $data)
{
    DB::beginTransaction();
    try {

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $osaCodeHeader = $this->generateOsaCode('SLH');

        $header = LoadHeader::create([
            'uuid'          => Str::uuid(),
            'osa_code'      => $osaCodeHeader,
            'salesman_id'   => $data['salesman_id'] ?? null,
            'route_id'      => $data['route_id'] ?? null,
            'warehouse_id'  => $data['warehouse_id'] ?? null,
            'load_date'     => $data['load_date'] ?? null,
            'remarks'       => $data['remarks'] ?? null,
            'latitude'      => $data['latitude'] ?? null,
            'longtitude'    => $data['longtitude'] ?? null,
            'salesman_type' => $data['salesman_type'] ?? null,
            'project_type'  => $data['project_type'] ?? null,
            'sync_time'     => $data['sync_time'] ?? null,
            'status'        => $data['status'] ?? 1,
        ]);

        if (!$header instanceof LoadHeader) {
            throw new \Exception('Failed to create header');
        }

        $warehouseId = $data['warehouse_id'];

        foreach ($data['details'] as $detail) {

            $itemId  = $detail['item_id'];
            $sentQty = (float) $detail['qty'];
            $uomId   = $detail['uom'];

            $item = Item::find($itemId);
            $itemName = $item ? $item->name : "Item ID: {$itemId}";

            $itemUom = DB::table('item_uoms')
                ->where('item_id', $itemId)
                ->where('uom_id', $uomId)
                ->first();

            if (!$itemUom || !$itemUom->upc) {
                DB::rollBack();
                return [
                    'status'  => 'error',
                    'message' => "Invalid UOM / UPC for {$itemName}"
                ];
            }

            $baseQty = $sentQty * (float) $itemUom->upc;

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->first();

            if (!$stock || $stock->qty < $baseQty) {
                DB::rollBack();
                return [
                    'status'  => 'error',
                    'message' => "Insufficient stock for {$itemName}"
                ];
            }

            $stock->update([
                'qty' => $stock->qty - $baseQty,
                'updated_user' => $data['created_user'] ?? null
            ]);

            LoadDetail::create([
                'uuid'      => Str::uuid(),
                'osa_code'  => $this->generateOsaCode('SLD'),
                'header_id' => $header->id,
                'item_id'   => $itemId,
                'uom'       => $uomId,
                'qty'       => $sentQty,
                'price'     => $detail['price'] ?? 0,
                'status'    => $detail['status'] ?? 1,
                'unload_status' => $detail['unload_status'] ?? 0,
            ]);
        }
        DB::commit();
        $workflow = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Load_Header')
            ->where('is_active', true)
            ->first();
        if ($workflow) {
            app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Load_Header',
                    'process_id'   => $header->id
                ]);
        }
        return $header->load([
            'details',
            'warehouse',
            'route',
            'salesman',
            'projecttype',
            'salesmantype',
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return [
            'status'  => 'error',
            'message' => $e->getMessage()
        ];
    }
}

private function generateOsaCode(string $prefix): string
    {
        $model = $prefix === 'SLH' ? new LoadHeader() : new LoadDetail();

        $lastRecord = $model->where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }


    // public function all($perPage = 50, $filters = [])
    // {
    //     try {
    //         $query = LoadHeader::with('details')->latest();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['osa_code'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch load headers: " . $e->getMessage());
    //     }
    // }


    // public function all($perPage = 50, $filters = [])
    // {
    //     try {
    //         $user = auth()->user();
    //         $query = LoadHeader::with([
    //             'details',
    //             'warehouse:id,warehouse_name,warehouse_code',
    //             'route:id,route_name,route_code',
    //             'salesman:id,name,osa_code',
    //             'salesmantype:id,salesman_type_name,salesman_type_code',
    //             'projecttype:id,name,osa_code'
    //         ])->latest();
    //         $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //         $allowedFilters = [
    //             'id',
    //             'uuid',
    //             'osa_code',
    //             'warehouse_id',
    //             'route_id',
    //             'salesman_id',
    //             'is_confirmed',
    //             'accept_time',
    //             'salesman_sign',
    //             'latitude',
    //             'longtitude',
    //             'created_user',
    //             'updated_user',
    //             'deleted_user',
    //             'sync_time',
    //             'load_id',
    //             'status',
    //             'salesman_type',
    //             'project_type'
    //         ];

    //         // ✅ Normal filters
    //         foreach ($filters as $field => $value) {
    //             if ($value !== null && $value !== '' && in_array($field, $allowedFilters)) {
    //                 switch ($field) {
    //                     case 'osa_code':
    //                     case 'salesman_sign':
    //                     case 'load_id':
    //                         $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                         break;

    //                     default:
    //                         $query->where($field, (int) $value);
    //                         break;
    //                 }
    //             }
    //         }

    //         if (!empty($filters['from_date']) && !empty($filters['todate'])) {
    //             $from = $filters['from_date'] . ' 00:00:00';
    //             $to   = $filters['todate'] . ' 23:59:59';

    //             $query->where(function ($q) use ($from, $to) {
    //                 $q->whereBetween('created_at', [$from, $to])
    //                     ->orWhereBetween('updated_at', [$from, $to]);
    //             });
    //         } elseif (!empty($filters['from_date'])) {
    //             $from = $filters['from_date'] . ' 00:00:00';
    //             $query->where(function ($q) use ($from) {
    //                 $q->where('created_at', '>=', $from)
    //                     ->orWhere('updated_at', '>=', $from);
    //             });
    //         } elseif (!empty($filters['todate'])) {
    //             $to = $filters['todate'] . ' 23:59:59';
    //             $query->where(function ($q) use ($to) {
    //                 $q->where('created_at', '<=', $to)
    //                     ->orWhere('updated_at', '<=', $to);
    //             });
    //         }


    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch load headers: " . $e->getMessage());
    //     }
    // }
    public function all($perPage = 50, $filters = [])
    {
        try {
            $user = auth()->user();

            $query = LoadHeader::with([
                'details',
                'warehouse:id,warehouse_name,warehouse_code',
                'route:id,route_name,route_code',
                'salesman:id,name,osa_code',
                'salesmantype:id,salesman_type_name,salesman_type_code',
                'projecttype:id,name,osa_code'
            ])->latest();

            $query = DataAccessHelper::filterAgentTransaction($query, $user);

            $allowedFilters = [
                'id',
                'uuid',
                'osa_code',
                'warehouse_id',
                'route_id',
                'salesman_id',
                'is_confirmed',
                'accept_time',
                'salesman_sign',
                'latitude',
                'longtitude',
                'created_user',
                'updated_user',
                'deleted_user',
                'sync_time',
                'load_id',
                'status',
                'salesman_type',
                'project_type'
            ];

            foreach ($filters as $field => $value) {
                if ($value !== null && $value !== '' && in_array($field, $allowedFilters)) {
                    switch ($field) {
                        case 'osa_code':
                        case 'salesman_sign':
                        case 'load_id':
                            $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                            break;

                        default:
                            $query->where($field, (int) $value);
                            break;
                    }
                }
            }

            if (!empty($filters['from_date']) && !empty($filters['todate'])) {
                $from = $filters['from_date'] . ' 00:00:00';
                $to   = $filters['todate'] . ' 23:59:59';

                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('updated_at', [$from, $to]);
                });
            } elseif (!empty($filters['from_date'])) {
                $from = $filters['from_date'] . ' 00:00:00';
                $query->where(function ($q) use ($from) {
                    $q->where('created_at', '>=', $from)
                    ->orWhere('updated_at', '>=', $from);
                });
            } elseif (!empty($filters['todate'])) {
                $to = $filters['todate'] . ' 23:59:59';
                $query->where(function ($q) use ($to) {
                    $q->where('created_at', '<=', $to)
                    ->orWhere('updated_at', '<=', $to);
                });
            }

            $loads = $query->paginate($perPage);

            /**
             * =======================================================
             * 🔥 Inject approval workflow status (SAVED PATTERN)
             * =======================================================
             */
            $loads->getCollection()->transform(function ($load) {

                $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Load_Header')
                    ->where('process_id', $load->id)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($workflowRequest) {

                    $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                        ->orderBy('step_order')
                        ->first();

                    $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->count();

                    $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->count();

                    $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->orderBy('step_order', 'DESC')
                        ->first();

                    $load->approval_status = $lastApprovedStep
                        ? $lastApprovedStep->message
                        : 'Initiated';

                    $load->current_step = $currentStep ? $currentStep->title : null;

                    $load->progress = $totalSteps > 0
                        ? ($completedSteps . '/' . $totalSteps)
                        : null;

                } else {
                    $load->approval_status = null;
                    $load->current_step    = null;
                    $load->progress        = null;
                }

                return $load;
            });

            return $loads;

        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch load headers: " . $e->getMessage());
        }
    }




    public function findByUuid(string $uuid)
    {
        return LoadHeader::with('details')->where('uuid', $uuid)->firstOrFail();
    }

    public function updateByUuid(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $header = LoadHeader::where('uuid', $uuid)->firstOrFail();

            $header->update([
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'salesman_id' => $data['salesman_id'] ?? $header->salesman_id,
                'is_confirmed' => $data['is_confirmed'] ?? $header->is_confirmed,
                'accept_time' => $data['accept_time'] ?? $header->accept_time,
                'salesman_sign' => $data['salesman_sign'] ?? $header->salesman_sign,
                'latitude' => $data['latitude'] ?? $header->latitude,
                'longtitude' => $data['longtitude'] ?? $header->longtitude,
                'salesman_type' => $data['salesman_type'] ?? $header->salesman_type,
                'project_type' => $data['project_type'] ?? $header->project_type,
                'status' => $data['status'] ?? $header->status,
            ]);

            if (!empty($data['details']) && is_array($data['details'])) {
                $existingDetailUuids = $header->details()->pluck('uuid')->toArray();
                $updatedUuids = [];

                foreach ($data['details'] as $detail) {
                    if (!empty($detail['uuid'])) {
                        $existingDetail = LoadDetail::where('uuid', $detail['uuid'])->first();
                        if ($existingDetail) {
                            $existingDetail->update([
                                'item_id' => $detail['item_id'],
                                'uom' => $detail['uom'],
                                'qty' => $detail['qty'],
                                'status' => $detail['status'],
                            ]);
                            $updatedUuids[] = $detail['uuid'];
                        }
                    } else {
                        $osaCodeDetail = $this->generateOsaCode('SLD');
                        $newDetail = LoadDetail::create([
                            'uuid' => Str::uuid(),
                            'osa_code' => $osaCodeDetail,
                            'header_id' => $header->id,
                            'item_id' => $detail['item_id'],
                            'uom' => $detail['uom'],
                            'qty' => $detail['qty'],
                            'status' => $detail['status'],
                        ]);
                        $updatedUuids[] = $newDetail->uuid;
                    }
                }

                $detailsToDelete = array_diff($existingDetailUuids, $updatedUuids);
                if (!empty($detailsToDelete)) {
                    LoadDetail::whereIn('uuid', $detailsToDelete)->delete();
                }
            }

            DB::commit();
            return $header->load('details');
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Load update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Load update failed: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $header = LoadHeader::where('uuid', $uuid)->firstOrFail();
            $header->details()->delete();
            return $header->delete();
        });
    }
}
