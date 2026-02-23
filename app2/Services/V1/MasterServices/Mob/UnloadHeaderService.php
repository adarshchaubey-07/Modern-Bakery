<?php

namespace App\Services\V1\MasterServices\Mob;

use App\Models\Agent_Transaction\UnloadDetail;
use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use App\Models\WarehouseStock;
use App\Models\Item;
use Illuminate\Support\Collection;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Models\ItemUOM;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\DataAccessHelper;
use App\Models\Agent_Transaction\LoadDetail;

class UnloadHeaderService
{
    // public function store(array $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $osaCodeHeader = $this->generateOsaCode('SUH');
    //         $salesmanId = $data['salesman_id'];
    //         $unloadNo = $this->generateUnloadNo($salesmanId);
    //         $header = UnloadHeader::create([
    //             'uuid' => Str::uuid(),
    //             'osa_code' => $osaCodeHeader,
    //             'warehouse_id' => $data['warehouse_id'] ?? null,
    //             'route_id' => $data['route_id'] ?? null,
    //             'salesman_id' => $salesmanId,
    //             'unload_no' => $unloadNo,
    //             'unload_date' => $data['unload_date'] ?? null,
    //             'unload_time' => now()->toTimeString(),
    //             'latitude' => $data['latitude'] ?? null,
    //             'longtitude' => $data['longtitude'] ?? null,
    //             'salesman_type' => $data['salesman_type'] ?? null,
    //             'project_type' => $data['project_type'] ?? null,
    //             'unload_from' => $data['unload_from'] ?? 'Backend',
    //             'load_date' => $data['load_date'] ?? null,
    //             'status' => 1
    //         ]);
    //         foreach ($data['details'] as $detail) {
    //             $osaCodeDetail = $this->generateOsaCode('SUD');
    //             UnloadDetail::create([
    //                 'uuid' => Str::uuid(),
    //                 'osa_code' => $osaCodeDetail,
    //                 'header_id' => $header->id,
    //                 'item_id' => $detail['item_id'],
    //                 'uom' => $detail['uom'],
    //                 'qty' => $detail['qty'],
    //                 'status' => 1
    //             ]);
    //         }

    //         DB::commit();
    //         return $header->load('details');
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Unload creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         throw new \Exception('Unload creation failed: ' . $e->getMessage());
    //     }
    // }


    // public function store(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $osaCodeHeader = $this->generateOsaCode('SUH');
    //         $salesmanId = $data['salesman_id'];
    //         $unloadNo = $this->generateUnloadNo($salesmanId);

    //         // ?? Auto-fetch warehouse_id via Route model (Eloquent)
    //         $warehouseId = $data['warehouse_id'] ?? null;

    //         if (!$warehouseId && !empty($data['route_id'])) {
    //             $route = Route::find($data['route_id']);
    //             $warehouseId = $route?->warehouse_id;
    //         }
    //         // ?? Create UnloadHeader
    //         $header = UnloadHeader::create([
    //             'uuid' => Str::uuid(),
    //             'osa_code' => $osaCodeHeader,
    //             'warehouse_id' => $warehouseId,
    //             'route_id' => $data['route_id'] ?? null,
    //             'salesman_id' => $salesmanId,
    //             'unload_no' => $unloadNo,
    //             'unload_date' => $data['unload_date'] ?? null,
    //             'unload_time' => now()->toTimeString(),
    //             'latitude' => $data['latitude'] ?? null,
    //             'longtitude' => $data['longtitude'] ?? null,
    //             'salesman_type' => $data['salesman_type'] ?? null,
    //             'project_type' => $data['project_type'] ?? null,
    //             'unload_from' => $data['unload_from'] ?? 'Backend',
    //             'load_date' => $data['load_date'] ?? null,
    //             'status' => 1
    //         ]);

    //         // ?? Create UnloadDetails
    //         foreach ($data['details'] as $detail) {
    //             $osaCodeDetail = $this->generateOsaCode('SUD');

    //             UnloadDetail::create([
    //                 'uuid' => Str::uuid(),
    //                 'osa_code' => $osaCodeDetail,
    //                 'header_id' => $header->id,
    //                 'item_id' => $detail['item_id'],
    //                 // 'uom' => $detail['uom'],
    //                 'qty' => $detail['qty'],
    //                 'status' => 1
    //             ]);
    //         }

    //         DB::commit();
    //         return $header->load('details');
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Unload creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw new \Exception('Unload creation failed: ' . $e->getMessage());
    //     }
    // }

    // public function store(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $osaCodeHeader = $this->generateOsaCode('SUH');
    //         $salesmanId = $data['salesman_id'];
    //         $unloadNo = $this->generateUnloadNo($salesmanId);

    //         // ?? Determine warehouse_id
    //         $warehouseId = $data['warehouse_id'] ?? null;

    //         if (!$warehouseId && !empty($data['route_id'])) {
    //             $route = Route::find($data['route_id']);
    //             $warehouseId = $route?->warehouse_id;
    //         }

    //         if (!$warehouseId) {
    //             throw new \Exception("warehouse_id not found.");
    //         }

    //         // ?? Create Unload Header
    //         $header = UnloadHeader::create([
    //             'uuid'          => Str::uuid(),
    //             'osa_code'      => $osaCodeHeader,
    //             'warehouse_id'  => $warehouseId,
    //             'route_id'      => $data['route_id'] ?? null,
    //             'salesman_id'   => $salesmanId,
    //             'unload_no'     => $unloadNo,
    //             'unload_date'   => $data['unload_date'] ?? null,
    //             'unload_time'   => now()->toTimeString(),
    //             'latitude'      => $data['latitude'] ?? null,
    //             'longtitude'    => $data['longtitude'] ?? null,
    //             'salesman_type' => $data['salesman_type'] ?? null,
    //             'project_type'  => $data['project_type'] ?? null,
    //             'unload_from'   => $data['unload_from'] ?? 'Backend',
    //             'load_date'     => $data['load_date'] ?? null,
    //             'status'        => 1
    //         ]);

    //         // ?? Process Details + Update Stock
    //         // dd($data['details']);
    //         foreach ($data['details'] as $detail) {

    //             $itemId = $detail['item_id'];
    //             $qty = (float)$detail['qty'];
    //             $uomId = $detail['uom'] ?? NULL;
    //             if (!$uomId) {
    //                 throw new \Exception("UOM missing for item_id {$itemId}");
    //             }

    //             // ?? 1?? Fetch item UOM conversion (UPC)
    //             $itemUom = DB::table('item_uoms')
    //                 ->where('item_id', $itemId)
    //                 ->where('uom_id', $uomId)
    //                 ->first();

    //             if (!$itemUom) {
    //                 throw new \Exception("UOM not found for item {$itemId} (uom: {$uomId})");
    //             }

    //             if (!$itemUom->upc) {
    //                 throw new \Exception("UPC missing for item {$itemId} (uom: {$uomId})");
    //             }

    //             // ?? 2?? Convert unload qty into base units
    //             $convertedQty = $qty * (float)$itemUom->upc;
    //             // dd($convertedQty);
    //             // ?? 3?? Fetch warehouse stock row
    //             $stock = WarehouseStock::where('warehouse_id', $warehouseId)
    //                 ->where('item_id', $itemId)
    //                 ->first();

    //             if (!$stock) {
    //                 throw new \Exception("Stock row not found for Item {$itemId} in Warehouse {$warehouseId}");
    //             }

    //             // ?? 4?? Update stock (ADD because this is unload)
    //             $newQty = $stock->qty + $convertedQty;

    //             $stock->update([
    //                 'qty' => $newQty,
    //                 'updated_user' => $salesmanId
    //             ]);

    //             // ?? 5?? Create Unload Detail
    //             UnloadDetail::create([
    //                 'uuid'      => Str::uuid(),
    //                 'osa_code'  => $this->generateOsaCode('SUD'),
    //                 'header_id' => $header->id,
    //                 'item_id'   => $itemId,
    //                 'uom'       => $uomId,
    //                 'qty'       => $qty,
    //                 'status'    => 1
    //             ]);
    //         }

    //         DB::commit();

    //         // ALWAYS return model (not array)
    //         return $header->load('details');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Unload creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         // Controller will catch this
    //         throw new \Exception("Unload creation failed: " . $e->getMessage());
    //     }
    // }


    public function store(array $data)
    {
        DB::beginTransaction();

        try {

            $osaCodeHeader = $this->generateOsaCode('SUH');
            $salesmanId = $data['salesman_id'];
            $unloadNo = $this->generateUnloadNo($salesmanId);

            $warehouseId = $data['warehouse_id'] ?? null;

            if (!$warehouseId && !empty($data['route_id'])) {
                $route = Route::find($data['route_id']);
                $warehouseId = $route?->warehouse_id;
            }

            if (!$warehouseId) {
                throw new \Exception("warehouse_id not found.");
            }

            $header = UnloadHeader::create([
                'uuid'          => Str::uuid(),
                'osa_code'      => $osaCodeHeader,
                'warehouse_id'  => $warehouseId,
                'route_id'      => $data['route_id'] ?? null,
                'salesman_id'   => $salesmanId,
                'unload_no'     => $unloadNo,
                'unload_date'   => $data['unload_date'] ?? null,
                'unload_time'   => now()->toTimeString(),
                'latitude'      => $data['latitude'] ?? null,
                'longtitude'    => $data['longtitude'] ?? null,
                'salesman_type' => $data['salesman_type'] ?? null,
                'project_type'  => $data['project_type'] ?? null,
                'unload_from'   => $data['unload_from'] ?? 'salesman',
                'load_date'     => $data['load_date'] ?? null,
                'status'        => 1
            ]);

            foreach ($data['details'] as $detail) {

                $itemId = $detail['item_id'];
                $qty    = (float) $detail['qty'];
                $uomId  = $detail['uom'] ?? null;

                if (!$uomId) {
                    throw new \Exception("UOM missing for item_id {$itemId}");
                }

                $itemUom = DB::table('item_uoms')
                    ->where('item_id', $itemId)
                    ->where('uom_id', $uomId)
                    ->first();

                if (!$itemUom || !$itemUom->upc) {
                    throw new \Exception("Invalid UOM/UPC for item {$itemId}");
                }

                $convertedQty = $qty * (float) $itemUom->upc;

                // ?? Fetch stock row
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->first();

                if (!$stock) {
                    throw new \Exception("Stock missing for item {$itemId}");
                }

                $newQty = $stock->qty + $convertedQty;

                $stock->update([
                    'qty'          => $newQty,
                    'updated_user' => $salesmanId
                ]);

                UnloadDetail::create([
                    'uuid'      => Str::uuid(),
                    'osa_code'  => $this->generateOsaCode('SUD'),
                    'header_id' => $header->id,
                    'item_id'   => $itemId,
                    'uom'       => $uomId,
                    'qty'       => $qty,
                    'status'    => 1
                ]);

                LoadDetail::where('item_id', $itemId)
                    ->where('unload_status', 0)
                    ->whereIn('header_id', function ($q) use ($salesmanId) {
                        $q->select('id')
                            ->from('tbl_load_header')
                            ->where('salesman_id', $salesmanId);
                    })
                    ->update(['unload_status' => 1]);
            }

            $pendingDetails = LoadDetail::where('unload_status', 0)
                ->whereIn('header_id', function ($q) use ($salesmanId) {
                    $q->select('id')
                        ->from('tbl_load_header')
                        ->where('salesman_id', $salesmanId);
                })
                ->count();

            if ($pendingDetails === 0) {
                LoadHeader::where('salesman_id', $salesmanId)
                    ->update([
                        'status'       => 1, 
                    ]);
            }

            DB::commit();

            return $header->load('details');
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Unload creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Unload creation failed: " . $e->getMessage());
        }
    }


    private function generateUnloadNo($salesmanId)
    {
        $salesman = Salesman::find($salesmanId);
        if (!$salesman || !$salesman->osa_code) {
            throw new \Exception('Salesman code not found for ID ' . $salesmanId);
        }

        $salesmanCode = $salesman->osa_code;

        $lastUnload = UnloadHeader::where('unload_no', 'LIKE', "{$salesmanCode}%")
            ->orderByDesc('id')
            ->first();

        if ($lastUnload && preg_match('/' . preg_quote($salesmanCode, '/') . '(\d+)$/', $lastUnload->unload_no, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        return sprintf('%s%04d', $salesmanCode, $nextNumber);
    }

    private function generateOsaCode(string $prefix): string
    {
        $model = $prefix === 'SUH' ? new UnloadHeader() : new UnloadDetail();

        $lastRecord = $model->where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }
    
public function all($perPage = 50, $filters = [])
    {
        try {
            // $user = auth()->user();
            $query = UnloadHeader::with(['details', 'salesman', 'warehouse', 'route'])->latest();
            // $query = DataAccessHelper::filterAgentTransaction($query, $user);
            $query = $this->applyFilters($query, $filters);

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch unload headers: " . $e->getMessage());
        }
    }

    /**
     * Apply filter conditions to UnloadHeader query.
     */
private function applyFilters($query, array $filters)
    {
        $warehouse_ids = collect();
        if (!empty($filters['region_id'])) {
            $warehouse_ids = Warehouse::where('region_id', $filters['region_id'])->pluck('id');
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(osa_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(unload_no) LIKE ?', ["%{$search}%"]);
            });
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        } elseif (!empty($filters['region_id'])) {
            if ($warehouse_ids->isNotEmpty()) {
                $query->whereIn('warehouse_id', $warehouse_ids);
            } else {
                $query->whereRaw('1 = 0'); // no warehouses in this region
            }
        }

        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        } elseif (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        } elseif (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}