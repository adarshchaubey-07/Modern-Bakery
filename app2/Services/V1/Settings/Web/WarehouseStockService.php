<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\WarehouseStock;
use App\Models\StockTransferHeader;
use App\Models\StockTransferDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WarehouseStockService
{
    public function list($perPage = 50, array $filters = [])
    {
        try {
            $query = WarehouseStock::with(['warehouse', 'item'])->orderByDesc('id');
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("[WarehouseStockService] Error fetching warehouse stocks", [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Unable to fetch warehouse stock list. Please try again later.");
        }
    }

    public function generateCode(): string
    {
        do {
            $last = WarehouseStock::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'WHS' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (WarehouseStock::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    // public function create(array $data)
    //     {
    //         DB::beginTransaction();
    //         try {
    //             dd($data);
    //             $data = array_merge($data, [
    //                 'uuid'=> $data['uuid'] ?? Str::uuid()->toString(),
    //                 'osa_code'=> $this->generateCode(),
    //             ]);
    //             $keepingUom = ItemUOM::where('item_id', $data['item_id'])
    //                 ->where('is_stock_keeping', true)
    //                 ->first();
    //             $data['qty'] = $keepingUom ? $keepingUom->keeping_quantity : 0;
    //             $stock = WarehouseStock::create($data);
    //             DB::commit();
    //             return $stock;
    //         } catch (Throwable $e) {
    //             DB::rollBack();
    //             Log::error("Failed to create Warehouse Stock", [
    //                 'data' => $data,
    //                 'error' => $e->getMessage(),
    //             ]);
    //             throw new \Exception('Unable to create Warehouse Stock. Please try again later.');
    //         }
    //     }
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            // ✅ Only generate if not provided
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $data['osa_code'] ?? $this->generateCode(),
            ]);
            $stock = WarehouseStock::create($data);

            DB::commit();
            return $stock;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("❌ Failed to create Warehouse Stock", [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to create Warehouse Stock. Please try again later.');
        }
    }


    public function getByUuid(string $uuid)
    {
        try {
            return WarehouseStock::where('uuid', $uuid)->firstOrFail();
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error fetching warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Warehouse stock not found.");
        }
    }


    /**
     * Update warehouse stock by UUID
     */
    public function update(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $stock = WarehouseStock::withTrashed()->where('uuid', $uuid)->firstOrFail();

            if (isset($data['item_id'])) {
                $keepingUom = ItemUom::where('item_id', $data['item_id'])
                    ->where('is_stock_keeping', true)
                    ->first();

                $data['qty'] = $keepingUom ? $keepingUom->keeping_quantity : 0;
            }

            $stock->update($data);
            DB::commit();

            return [
                'status' => true,
                'message' => '✅ Warehouse Stock updated successfully.',
                'data' => $stock,
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("❌ [WarehouseStockService] Failed to update Warehouse Stock", [
                'uuid' => $uuid,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to update Warehouse Stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Soft delete warehouse stock
     */
    public function softDelete(string $uuid)
    {
        try {
            $stock = WarehouseStock::where('uuid', $uuid)->firstOrFail();
            $stock->delete();

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Warehouse Stock deleted successfully.',
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error soft deleting warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to delete warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore soft-deleted warehouse stock
     */
    public function restore(string $uuid)
    {
        try {
            $stock = WarehouseStock::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $stock->restore();

            return [
                'status' => true,
                'message' => 'Warehouse Stock restored successfully.',
                'data' => $stock,
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error restoring warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to restore warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Permanently delete warehouse stock
     */
    public function forceDelete(string $uuid)
    {
        try {
            $stock = WarehouseStock::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $stock->forceDelete();

            return [
                'status' => true,
                'message' => 'Warehouse Stock permanently deleted from the system.',
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error permanently deleting warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to permanently delete warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function warehouseStocklist(Request $request, $id)
    {
        $query = $request->query('query');
        $perPage = $request->get('per_page', 50);

        return WarehouseStock::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'item:id,code,name'
        ])
            ->where('warehouse_id', $id)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('osa_code', 'ILIKE', "%{$query}%")
                        ->orWhereRaw("CAST(qty AS TEXT) ILIKE ?", ["%{$query}%"])
                        ->orWhereRaw("CAST(status AS TEXT) ILIKE ?", ["%{$query}%"])
                        ->orWhereHas('item', function ($item) use ($query) {
                            $item->where('code', 'ILIKE', "%{$query}%")
                                ->orWhere('name', 'ILIKE', "%{$query}%");
                            // ->orWhere('upc', 'ILIKE', "%{$query}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouse) use ($query) {
                            $warehouse->where('warehouse_name', 'ILIKE', "%{$query}%")
                                ->orWhere('warehouse_code', 'ILIKE', "%{$query}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function checkStockAvailability($itemId, $uomId, $quantity, $warehouseId)
    {

        $item = Item::find($itemId);
        if (!$item) {
            return [
                'status' => 'error',
                'message' => 'Item not found.'
            ];
        }

        $itemUom = ItemUOM::where('item_id', $itemId)
            ->where('uom_id', $uomId)
            ->first();

        if (!$itemUom) {
            return [
                'status' => 'error',
                'message' => 'UOM not found for this item.'
            ];
        }


        $stock = WarehouseStock::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->value('qty') ?? 0;


        $upc = is_numeric($item->upc) ? (int) $item->upc : 1;

        // dd($upc);

        $requiredPcs = $quantity * $upc;
        $availablePcs = $stock * $upc;


        if ($availablePcs < $requiredPcs) {
            return [
                'status' => 'error',
                'message' => 'Insufficient stock in the given Warehouse.',
                'available_stock_in_pcs' => $availablePcs,
                'required_stock_in_pcs' => $requiredPcs
            ];
        }


        $pricePerPcs = $itemUom->price / $upc;
        // $totalPrice = $requiredPcs * $pricePerPcs;

        return [
            'status' => 'success',
            'message' => 'Stock is available for the given Warehouse.',
            'available_stock_in_pcs' => $availablePcs,
            'request_stock_in_pcs' => $requiredPcs,
            'price_per_pcs' => round($pricePerPcs, 2),
            // 'total_price' => round($totalPrice, 2),
        ];
    }

    public function getWarehouseValuation($warehouseId)
    {
        $result = DB::table('tbl_warehouse_stocks as ws')
            ->join('item_uoms as iu', 'ws.item_id', '=', 'iu.item_id')
            ->where('ws.warehouse_id', $warehouseId)
            ->where('iu.uom_type', 'primary')
            ->selectRaw('SUM(ws.qty * iu.price) as total_valuation')
            ->selectRaw('SUM(ws.qty) as total_qty')
            ->first();
        return [
            'total_qty' => $result->total_qty ?? 0,
            'total_valuation' => $result->total_valuation ?? 0
        ];
    }
    public function getLoadedStockDetails($warehouseId)
    {
        $today = Carbon::today()->toDateString();
        $headerIds = DB::table('tbl_load_header')
            ->where('warehouse_id', $warehouseId)
            ->whereDate('created_at', $today)
            ->pluck('id');

        if ($headerIds->isEmpty()) {
            return [
                'total_loaded_qty' => 0,
                'details' => []
            ];
        }
        $details = DB::table('tbl_load_details')
            ->whereIn('header_id', $headerIds)
            ->select('item_id', DB::raw('SUM(qty) AS loaded_qty'))
            ->groupBy('item_id')
            ->get();
        $totalLoadedQty = $details->sum('loaded_qty');

        return [
            'total_loaded_qty' => $totalLoadedQty,
            'details' => $details
        ];
    }
    public function getSalesValuation($warehouseId, $days = null)
    {
        $query = DB::table('agent_order_headers')
            ->where('warehouse_id', $warehouseId);
        if ($days) {
            $startDate = Carbon::today()->subDays($days)->toDateString();
            $query->whereDate('created_at', '>=', $startDate);
        }
        $orderIds = $query->pluck('id');
        if ($orderIds->isEmpty()) {
            return [
                'total_valuation' => 0,
                'details' => []
            ];
        }
        $details = DB::table('agent_order_details AS aod')
            ->join('item_uoms AS iu', function ($join) {
                $join->on('aod.item_id', '=', 'iu.item_id')
                    ->where('iu.uom_type', '=', 'primary');
            })
            ->whereIn('aod.header_id', $orderIds)
            ->select(
                'aod.item_id',
                DB::raw('SUM(aod.quantity) AS total_qty'),
                'iu.price',
                DB::raw('(SUM(aod.quantity) * iu.price) AS valuation')
            )
            ->groupBy('aod.item_id', 'iu.price')
            ->get();
        $totalValuation = $details->sum('valuation');
        return [
            'total_valuation' => $totalValuation,
            'details' => $details
        ];
    }
    public function getHighQuantityItems($warehouseId)
    {
        $items = DB::table('tbl_warehouse_stocks as ws')
            ->join('items as i', 'ws.item_id', '=', 'i.id')
            ->where('ws.warehouse_id', $warehouseId)
            ->where('ws.qty', '<=', 100)
            ->select(
                'ws.item_id',
                'i.name as item_name',
                'ws.qty'
            )
            ->orderBy('ws.qty', 'desc')
            ->get();
        return $items;
    }
    public function getWarehouseStockFullDetails($warehouseId, $days = null, $months = null, $isPromo = null)
    {
        $stockDateFilter = null;
        $salesDateFilter = null;

        if (!empty($days)) {
            $stockDateFilter = now()->subDays($days);
            $salesDateFilter = now()->subDays($days);
        }

        if (!empty($months)) {
            $start = now()->subMonths($months)->startOfMonth();
            $end = now()->subMonths($months)->endOfMonth();

            $stockDateFilter = [$start, $end];
            $salesDateFilter = [$start, $end];
        }

        $stocks = DB::table('tbl_warehouse_stocks as ws')
            ->join('items as i', 'ws.item_id', '=', 'i.id')
            ->leftJoin('pricing_details as pd', 'pd.item_id', '=', 'ws.item_id')
            ->join('tbl_warehouse as w', 'ws.warehouse_id', '=', 'w.id')
            ->select(
                'ws.id',
                'ws.item_id',
                'i.name as item_name',
                'i.code as item_code',
                'i.erp_code',
                'ws.qty as stock_qty',
                'ws.warehouse_id',
                'w.warehouse_name as warehouse_name',
                'w.warehouse_code as warehouse_code',
                'pd.buom_ctn_price',
                'pd.auom_pc_price'
            )
            ->where('ws.warehouse_id', $warehouseId);
        if (!is_null($isPromo)) {
            $isPromoBool = filter_var($isPromo, FILTER_VALIDATE_BOOLEAN);
            $stocks->where('i.is_promotional', $isPromoBool);
        }


        if (!empty($days)) {
            $stocks->whereDate('ws.created_at', '>=', $stockDateFilter);
        }

        if (!empty($months)) {
            $stocks->whereBetween('ws.created_at', $stockDateFilter);
        }

        $stocks = $stocks->get();

        $uoms = DB::table('item_uoms')
            ->select('id', 'item_id', 'name', 'uom_type', 'upc', 'price','uom_id')
            ->orderBy('uom_type', 'asc')
            ->get()
            ->groupBy('item_id');

        $sales = DB::table('agent_order_headers as aoh')
            ->join('agent_order_details as aod', 'aoh.id', '=', 'aod.header_id')
            ->where('aoh.warehouse_id', $warehouseId);

        if (!empty($days)) {
            $sales->whereDate('aoh.created_at', '>=', $salesDateFilter);
        }

        if (!empty($months)) {
            $sales->whereBetween('aoh.created_at', $salesDateFilter);
        }

        $sales = $sales->select(
            'aod.item_id',
            DB::raw('SUM(aod.quantity) as total_sold')
        )
            ->groupBy('aod.item_id')
            ->get()
            ->keyBy('item_id');

        $final = $stocks->map(function ($item) use ($sales, $uoms) {

            $item->uoms = $uoms[$item->item_id] ?? [];

            $item->total_sold_qty = $sales[$item->item_id]->total_sold ?? 0;

            $item->purchase = 0;

            return $item;
        });

        return $final;
    }


    public function listByWarehouse(int $warehouseId): Collection
    {
        try {

            return WarehouseStock::query()
                ->with([
                    'warehouse:id,warehouse_name,warehouse_code',
                    'item:id,name,erp_code'
                ])
                ->where('warehouse_id', $warehouseId)
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($stock) {
                    return [
                        'id'   => $stock->id,
                        'warehouse'  => [
                            'id'   => $stock->warehouse->id ?? null,
                            'name' => $stock->warehouse->warehouse_name ?? null,
                            'code' => $stock->warehouse->warehouse_code ?? null,
                        ],
                        'item' => [
                            'id'       => $stock->item->id ?? null,
                            'name'     => $stock->item->name ?? null,
                            'erp_code' => $stock->item->erp_code ?? null,
                        ],
                        'qty'        => $stock->qty,
                        'status'     => $stock->status,
                    ];
                });
        } catch (Throwable $e) {
            dd($e);
            Log::error('[WarehouseStockService] Error fetching warehouse stock list', [
                'warehouse_id' => $warehouseId,
                'error'        => $e->getMessage(),
            ]);

            throw new \Exception(
                'Unable to fetch warehouse stock data. Please try again later.'
            );
        }
    }

    // public function bulkTransferStock(array $data): array
    // {
    //     DB::beginTransaction();

    //     try {
    //         $results = [];

    //         foreach ($data['items'] as $item) {

    //             $fromStock = WarehouseStock::where('warehouse_id', $data['from_warehouse'])
    //                 ->where('item_id', $item['item_id'])
    //                 ->whereNull('deleted_at')
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (!$fromStock) {
    //                 throw new \Exception(
    //                     "Stock not found for item_id {$item['item_id']} in source warehouse."
    //                 );
    //             }

    //             if ($fromStock->qty < $item['qty']) {
    //                 throw new \Exception(
    //                     "Insufficient stock for item_id {$item['item_id']}."
    //                 );
    //             }

    //             $fromStock->qty -= $item['qty'];
    //             $fromStock->save();

    //             $toStock = WarehouseStock::where('warehouse_id', $data['to_warehouse'])
    //                 ->where('item_id', $item['item_id'])
    //                 ->whereNull('deleted_at')
    //                 ->lockForUpdate()
    //                 ->first();

    //             if ($toStock) {
    //                 $toStock->qty += $item['qty'];
    //                 $toStock->save();
    //             } else {
    //                 $toStock = WarehouseStock::create([
    //                     'warehouse_id' => $data['to_warehouse'],
    //                     'item_id'      => $item['item_id'],
    //                     'qty'          => $item['qty'],
    //                     'status'       => 1,
    //                 ]);
    //             }

    //             $results[] = [
    //                 'item_id'         => $item['item_id'],
    //                 'transferred_qty' => $item['qty'],
    //                 'available_qty'   => $fromStock->qty,
    //             ];
    //         }

    //         DB::commit();

    //         return [
    //             'from_warehouse' => $data['from_warehouse'],
    //             'to_warehouse'   => $data['to_warehouse'],
    //             'items'             => $results,
    //         ];
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error('[WarehouseStockService] Bulk stock transfer failed', [
    //             'payload' => $data,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         throw new \Exception(
    //             'Bulk stock transfer failed. ' . $e->getMessage()
    //         );
    //     }
    // }



    // public function bulkTransferStock(array $data): array
    // {
    //     DB::beginTransaction();

    //     try {

    //         // ✅ STEP 1: Create Stock Transfer Header
    //         $header = StockTransferHeader::create([
    //             'osa_code'     => $this->generateTransferCode(),
    //             'source_warehouse' => $data['from_warehouse'],
    //             'destiny_warehouse'   => $data['to_warehouse'],
    //             'status'            => 1,
    //             'created_user'      => auth()->id(),
    //         ]);

    //         $results = [];

    //         // ✅ STEP 2: Process Items
    //         foreach ($data['items'] as $item) {

    //             // 🔹 Source warehouse stock
    //             $fromStock = WarehouseStock::where('warehouse_id', $data['from_warehouse'])
    //                 ->where('item_id', $item['item_id'])
    //                 ->whereNull('deleted_at')
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (!$fromStock) {
    //                 throw new \Exception(
    //                     "Stock not found for item_id {$item['item_id']} in source warehouse."
    //                 );
    //             }

    //             if ($fromStock->qty < $item['qty']) {
    //                 throw new \Exception(
    //                     "Insufficient stock for item_id {$item['item_id']}."
    //                 );
    //             }

    //             // 🔹 Deduct from source
    //             $fromStock->qty -= $item['qty'];
    //             $fromStock->save();

    //             // 🔹 Destination warehouse stock
    //             $toStock = WarehouseStock::where('warehouse_id', $data['to_warehouse'])
    //                 ->where('item_id', $item['item_id'])
    //                 ->whereNull('deleted_at')
    //                 ->lockForUpdate()
    //                 ->first();

    //             if ($toStock) {
    //                 $toStock->qty += $item['qty'];
    //                 $toStock->save();
    //             } else {
    //                 $toStock = WarehouseStock::create([
    //                     'warehouse_id' => $data['to_warehouse'],
    //                     'item_id'      => $item['item_id'],
    //                     'qty'          => $item['qty'],
    //                     'status'       => 1,
    //                     'created_user' => auth()->id(),
    //                 ]);
    //             }

    //             // ✅ STEP 3: Create Stock Transfer Detail
    //             StockTransferDetail::create([
    //                 'header_id' => $header->id,
    //                 'item_id'                  => $item['item_id'],
    //                 'transfer_qty'             => $item['qty'],
    //                 'created_user'             => auth()->id(),
    //             ]);

    //             $results[] = [
    //                 'item_id'         => $item['item_id'],
    //                 'transferred_qty' => $item['qty'],
    //                 'available_qty'   => $fromStock->qty,
    //             ];
    //         }

    //         DB::commit();

    //         return [
    //             'id'    => $header->id,
    //             'osa_code'  => $header->osa_code,
    //             'from_warehouse' => $data['from_warehouse'],
    //             'to_warehouse'   => $data['to_warehouse'],
    //             'items'          => $results,
    //         ];
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error('[WarehouseStockService] Bulk stock transfer failed', [
    //             'payload' => $data,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         throw new \Exception(
    //             'Bulk stock transfer failed. ' . $e->getMessage()
    //         );
    //     }
    // }
    public function bulkTransferStock(array $data): array
    {
        DB::beginTransaction();

        try {

            $user = auth()->user();
            $header = StockTransferHeader::create([
                'osa_code'          => $this->generateTransferCode(),
                'source_warehouse' => $data['from_warehouse'],
                'destiny_warehouse' => $data['to_warehouse'],
                'status'            => 1,
                'created_user'      => $user->id,
            ]);
            $results = [];
            foreach ($data['items'] as $item) {

                $fromStock = WarehouseStock::where('warehouse_id', $data['from_warehouse'])
                    ->where('item_id', $item['item_id'])
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$fromStock) {
                    throw new \Exception(
                        "Stock not found for item_id {$item['item_id']} in source warehouse."
                    );
                }

                if ($fromStock->qty < $item['qty']) {
                    throw new \Exception(
                        "Insufficient stock for item_id {$item['item_id']}."
                    );
                }

                $fromStock->update([
                    'qty' => $fromStock->qty - $item['qty']
                ]);

                $toStock = WarehouseStock::where('warehouse_id', $data['to_warehouse'])
                    ->where('item_id', $item['item_id'])
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($toStock) {
                    $toStock->increment('qty', $item['qty']);
                } else {
                    WarehouseStock::create([
                        'warehouse_id' => $data['to_warehouse'],
                        'item_id'      => $item['item_id'],
                        'qty'          => $item['qty'],
                        'status'       => 1,
                        'created_user' => $user->id,
                    ]);
                }

                StockTransferDetail::create([
                    'header_id'      => $header->id,
                    'item_id'        => $item['item_id'],
                    'transfer_qty'   => $item['qty'],
                    'created_user'   => $user->id,
                ]);

                $results[] = [
                    'item_id'         => $item['item_id'],
                    'transferred_qty' => $item['qty'],
                    'available_qty'   => $fromStock->qty,
                ];
            }

            DB::commit();

            /**
             * ======================================================
             * 🚀 APPLY WORKFLOW (SAVED GLOBAL PATTERN)
             * ======================================================
             */
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Distributor_Stock_Transfer')
                ->where('is_active', true)
                ->first();

            if ($workflow) {

                $approvalService = app(
                    \App\Services\V1\Approval_process\HtappWorkflowApprovalService::class
                );

                $approvalResult = $approvalService->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Distributor_Stock_Transfer',
                    'process_id'   => $header->id
                ]);

                /**
                 * ✅ AUTO-APPROVE IF ROLE = 1
                 */
                if ($user->role == 1 && isset($approvalResult['workflow_request_id'])) {
                    $approvalService->autoApproveAllSteps(
                        $approvalResult['workflow_request_id'],
                        $user->id
                    );
                }
            }

            return [
                'id'              => $header->id,
                'osa_code'        => $header->osa_code,
                'from_warehouse'  => $data['from_warehouse'],
                'to_warehouse'    => $data['to_warehouse'],
                'items'           => $results,
            ];
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('[WarehouseStockService] Bulk stock transfer failed', [
                'payload' => $data,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception(
                'Bulk stock transfer failed. ' . $e->getMessage()
            );
        }
    }

    public function generateTransferCode(): string
    {
        do {
            $last = StockTransferHeader::withTrashed()
                ->latest('id')
                ->first();

            $next = $last
                ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1
                : 1;

            $osa_code = 'STH' . str_pad($next, 5, '0', STR_PAD_LEFT);
        } while (
            StockTransferHeader::withTrashed()
            ->where('osa_code', $osa_code)
            ->exists()
        );
        return $osa_code;
    }
}
