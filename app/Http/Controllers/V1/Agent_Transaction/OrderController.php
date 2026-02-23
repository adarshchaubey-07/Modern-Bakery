<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreOrderRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateOrderRequest;
use App\Http\Resources\V1\Agent_Transaction\OrderHeaderResource;
use App\Services\V1\Agent_Transaction\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Exports\OrderHeaderFullExport;
use App\Exports\InvoiceItemExport;
use App\Exports\OrderHeaderDetailExport;
use App\Exports\OrderCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;


class OrderController extends Controller
{
    use ApiResponse;

    protected OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }


    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters = $request->except(['limit', 'dropdown']);
            $orders = $this->service->getAll($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'data' => OrderHeaderResource::collection($orders),
                ]);
            }
            $pagination = [
                'page' => $orders->currentPage(),
                'limit' => $orders->perPage(),
                'totalPages' => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];
            return $this->success(
                OrderHeaderResource::collection($orders),
                'Orders fetched successfully',
                200,
                $pagination
            );
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->except(['limit']);
            $orders = $this->service->globalFilter($perPage, $filters);
            $pagination = [
                'page'          => $orders->currentPage(),
                'limit'         => $orders->perPage(),
                'totalPages'    => $orders->lastPage(),
                'totalRecords'  => $orders->total(),
            ];
            return $this->success(
                OrderHeaderResource::collection($orders),
                'Orders fetched successfully',
                200,
                $pagination
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function show(string $uuid): JsonResponse
    {
        try {
            $order = $this->service->getByUuid($uuid);
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new OrderHeaderResource($order)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  public function store(StoreOrderRequest $request): JsonResponse
{
    try {
        $order = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code'   => 201,
            'data'   => new OrderHeaderResource($order)
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 400,
            'message' => 'Failed to create order & delivery',
            'error'   => $e->getMessage()
        ], 400);
    }
}

    public function update(UpdateOrderRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new OrderHeaderResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['warehouse_id', 'from_date', 'to_date']);
            $stats = $this->service->getStatistics($filters);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'uuid|exists:agent_order_headers,uuid',
            'status' => 'required|integer',
        ]);

        $orderIds = $request->input('order_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($orderIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Order statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }
    public function exportOrderHeader(Request $request)
    {
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $filename = 'order_header_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'orderexports/' . $filename;

    $filters = $request->input('filter', []);

    $fromDate = $filters['from_date'] ?? null;
    $toDate   = $filters['to_date'] ?? null;

    $export = new OrderHeaderFullExport($fromDate, $toDate, $filters);

    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportOrders(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');
        $filename = 'order_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;
        if ($format === 'pdf') {
            $order = OrderHeader::with(['warehouse', 'customer'])->where('uuid', $uuid)->firstOrFail();
            $orderDetails = OrderDetail::with(['item', 'uom'])->where('header_id', $order->id)->get();
            // dd($order);
            $pdf = \PDF::loadView('order', [
                'order' => $order,
                'orderDetails' => $orderDetails
            ]);
            \Storage::disk('public')->put($path, $pdf->output());
            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;
            return response()->json([
                'status' => 'success',
                'download_url' => $fullUrl
            ]);
        }
        $export = new OrderHeaderDetailExport($uuid);
        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;
        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }


    public function exportCollapseOrders(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Order_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'orderexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $export = new OrderCollapseExport(
            $fromDate,
            $toDate,
            $filters
        );

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportInvoiceByItem(Request $request)
    {
        $itemId = $request->input('item_id');
        $format = strtolower($request->input('format', 'xlsx'));

        if (!$itemId) {
            return response()->json([
                'status' => 'error',
                'message' => 'The item_id field is required.'
            ], 400);
        }
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'invoice_item_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceitemexports/' . $filename;

        $export = new InvoiceItemExport($itemId);
        if ($format === 'csv') {
            Excel::store($export, $path, \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'code'         => 200,
            'message'      => 'Invoice export generated successfully.',
            'download_url' => $fullUrl,
        ]);
    }

        public function confirmOrder($id)
    {
        $order = OrderHeader::with('details')->findOrFail($id);

        if ($order->status != 2) {
            $order->update(['status' => 2]);
            AgentDeliveryHeaderService::createFromOrder($order);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order confirmed and delivery created successfully'
        ]);
    }
}
