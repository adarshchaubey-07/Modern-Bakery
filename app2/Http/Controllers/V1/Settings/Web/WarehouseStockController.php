<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\WarehouseStockRequest;
use App\Http\Resources\V1\Settings\Web\WarehouseStockResource;
use App\Services\V1\Settings\Web\WarehouseStockService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Models\WarehouseStock;
use App\Exports\WarehouseStockExport;
use Maatwebsite\Excel\Facades\Excel;



/**
 * @OA\Schema(
 *     schema="WarehouseStock",
 *     type="object",
 *     title="WarehouseStock",
 *     description="Warehouse Stock schema",
 *     required={"warehouse_id", "item_id", "qty", "status"},
 *     @OA\Property(property="warehouse_id", type="integer", example=1, description="ID of the warehouse"),
 *     @OA\Property(property="item_id", type="integer", example=10, description="ID of the item"),
 *     @OA\Property(property="status", type="integer", example=1, description="Status of the stock: 0=Inactive, 1=Active")
 * )
 */
class WarehouseStockController extends Controller
{
    protected $service;
    use ApiResponse;

    public function __construct(WarehouseStockService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/list",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get all warehouse stocks",
     *     @OA\Response(response=200, description="Stocks fetched successfully")
     * )
     */
    public function index(Request $request)
    {

        $perPage = $request->get('per_page', 50);
        $filters = $request->only(['osa_code', 'warehouse_id', 'item_id', 'status']);
        $stocks = $this->service->list($perPage, $filters);

        return ResponseHelper::paginatedResponse(
            'Records fetched successfully',
            WarehouseStockResource::class,
            $stocks
        );
    }

    /**
     * @OA\Post(
     *     path="/api/settings/warehouse-stocks/add",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new warehouse stock",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WarehouseStock")
     *     ),
     *     @OA\Response(response=201, description="Stock created successfully")
     * )
     */
    public function store(WarehouseStockRequest $request)
    {
        $stock = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Warehouse stock created successfully',
            'data' => new WarehouseStockResource($stock),
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific warehouse stock by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Stock fetched successfully"),
     *     @OA\Response(response=404, description="Stock not found")
     * )
     */
    public function show(string $uuid)
    {
        try {
            $stock = $this->service->getByUuid($uuid);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Warehouse stock fetched successfully',
                'data' => new WarehouseStockResource($stock),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Warehouse stock not found.',
            ], 404);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update a warehouse stock",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/WarehouseStock")),
     *     @OA\Response(response=200, description="Stock updated successfully")
     * )
     */
    public function update(WarehouseStockRequest $request, string $uuid)
    {
        $result = $this->service->update($uuid, $request->validated());

        if (!$result['status']) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $result['message'],
            'data' => new WarehouseStockResource($result['data']),
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Soft delete a warehouse stock",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Stock soft deleted successfully")
     * )
     */
    public function destroy(string $uuid)
    {
        $result = $this->service->softDelete($uuid);

        return response()->json($result);
    }
    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/warehouseStockInfo/{id}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get Item Stock",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Stock fetched successfully"),
     *     @OA\Response(response=404, description="Stock not found")
     * )
     */
    public function warehouseStockInfo(Request $request, $id)
    {
        $warehouseStocks = $this->service->warehouseStocklist($request, $id);

        return ResponseHelper::paginatedResponse(
            'Warehouse Stock List',
            WarehouseStockResource::class,
            $warehouseStocks
        );
    }

    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/stock",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Check stock availability and price",
     *     description="Checks if stock is available for given item, uom, and quantity, and returns price per pcs.",
     *     @OA\Parameter(name="item_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="uom_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="quantity", in="query", required=true, @OA\Schema(type="number")),
     *     @OA\Parameter(name="warehouse_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=400, description="Bad Request")
     * )
     */
    public function checkStock(Request $request): JsonResponse
    {
        $itemId = $request->get('item_id');
        $uomId = $request->get('uom_id');
        $quantity = $request->get('quantity');
        $warehouseId = $request->get('warehouse_id');

        $result = $this->service->checkStockAvailability($itemId, $uomId, $quantity, $warehouseId);
        return response()->json($result);
    }
    // public function checkStock(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'item_id'      => 'required|integer',
    //         'uom_id'       => 'required|integer',
    //         'quantity'     => 'required|numeric|min:1',
    //         'warehouse_id' => 'required|integer',
    //     ]);

    //     $result = $this->service->checkStockAndPrice(
    //         $validated['item_id'],
    //         $validated['uom_id'],
    //         $validated['quantity'],
    //         $validated['warehouse_id']
    //     );

    //     return response()->json($result);
    // }

    public function getWarehouseSummary($warehouseId)
    {
        $totalValuation = $this->service->getWarehouseValuation($warehouseId);
        $loadedStock = $this->service->getLoadedStockDetails($warehouseId);
        $salesData  = $this->service->getSalesValuation($warehouseId);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Warehouse summary fetched successfully',
            'warehouse_id' => $warehouseId,
            'total_warehouse_valuation' => $totalValuation,
            'today_loaded_qty' => $loadedStock['total_loaded_qty'],
            'loaded_stock_details' => $loadedStock['details'],
            'sales_total_valuation' => $salesData['total_valuation'],
            'sales_details' => $salesData['details'],
            'sales_days_filter' => $days ?? 'all',
        ]);
    }
    public function getLatestOrders(Request $request, $warehouseId)
    {
        $days = $request->query('days', 30);
        $query = WarehouseStock::with(['warehouse', 'item'])
            ->where('warehouse_id', $warehouseId);
        if (!empty($days)) {
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }
        $orders = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $salesValuation = $this->service->getSalesValuation($warehouseId, $days);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'warehouse_id' => $warehouseId,
            'days_filter' => $days,
            'latest_orders' => WarehouseStockResource::collection($orders),
            'sales_valuation' => [
                'total_valuation' => $salesValuation['total_valuation'],
                'details' => $salesValuation['details']
            ]
        ]);
    }
    public function getHighQuantityItems(Request $request, $warehouseId)
    {
        $items = $this->service->getHighQuantityItems($warehouseId);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'warehouse_id' => $warehouseId,
            'count' => $items->count(),
            'items' => $items
        ]);
    }
    public function getWarehouseStockDetails(Request $request, $warehouseId)
    {
        $days = $request->get('days');
        $months = $request->get('months');
        $isPromo = $request->get('is_promo'); // 🔹 added
        $data = $this->service->getWarehouseStockFullDetails($warehouseId, $days, $months,$isPromo);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'filter_days' => $days,
            'filter_months' => $months,
            'warehouse_id' => $warehouseId,
            'total_items' => $data->count(),
            'stocks' => $data
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/{warehouseId}",
     *     summary="Get low stock items for a warehouse",
     *     description="Returns paginated list of items where qty <= 100 for the given warehouse.",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="warehouseId",
     *         in="path",
     *         required=true,
     *         description="ID of the warehouse",
     *         @OA\Schema(type="integer")
     *     ), 
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of records per page (default: 50)",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="stocks", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="warehouse_id", type="integer", example=5),
     *                     @OA\Property(property="item_id", type="integer", example=22),
     *                     @OA\Property(property="qty", type="number", example=80),
     *                     @OA\Property(property="updated_at", type="string", example="2025-02-12 12:30:00")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="total", type="integer", example=250)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Warehouse not found"
     *     )
     * )
     */

    public function LowStocks(Request $request, $warehouseId)
    {
        $perPage = $request->input('per_page', 50);

        $lowstocks = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('qty', '<=', 100)
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'stocks' => $lowstocks->items(),
            'pagination' => [
                'current_page' => $lowstocks->currentPage(),
                'last_page'    => $lowstocks->lastPage(),
                'per_page'     => $lowstocks->perPage(),
                'total'        => $lowstocks->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/export",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Export warehouse stock data (XLSX or CSV)",
     *     description="Exports warehouse stock records with optional date filtering. Returns a downloadable file URL.",
     *
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="Export format (xlsx or csv). Default: xlsx",
     *         @OA\Schema(type="string", enum={"xlsx", "csv"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=false,
     *         description="Filter records from this date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=false,
     *         description="Filter records up to this date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Export generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="download_url", type="string", example="https://example.com/storage/app/public/warehouseexports/file.xlsx")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     */
    public function exportWarehouseStocks(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $filename = 'warehouse_stock_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'warehouseexports/' . $filename;

        $export = new WarehouseStockExport($fromDate, $toDate);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function ItemsByWarehouse(int $warehouseId): JsonResponse
    {
        try {
            $data = $this->service->listByWarehouse($warehouseId);

            return response()->json([
                'status' => 'success',
                'count'  => $data->count(),
                'data'   => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function bulkTransfer(Request $request): JsonResponse
    // {
    //     try {
    //         $data = $request->validate([
    //             'from_warehouse'        => 'required|integer',
    //             'to_warehouse'          => 'required|integer|different:tbl_warehouse',
    //             'items'                    => 'required|array|min:1',
    //             'items.*.item_id'          => 'required|integer',
    //             'items.*.qty'              => 'required|numeric|min:1',
    //         ]);

    //         $result = $this->service->bulkTransferStock($data);

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Stock transferred successfully',
    //             'data'    => $result
    //         ], 200);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function bulkTransfer(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'from_warehouse'       => 'required|integer',
                'to_warehouse'         => 'required|integer|different:tbl_warehouse',
                'items'                => 'required|array|min:1',
                'items.*.item_id'      => 'required|integer',
                'items.*.qty'          => 'required|numeric|min:1',
            ]);

            $result = $this->service->bulkTransferStock($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Stock transferred successfully',
                'data'    => $result
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
