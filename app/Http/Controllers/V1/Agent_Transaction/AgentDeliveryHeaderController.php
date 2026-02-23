<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Exports\AgentDeliveryExport;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\AgentDeliveryHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\AgentDeliveryHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\AgentDeliveryHeaderResource;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Services\V1\Agent_Transaction\AgentDeliveryHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryHeaderExport;
use App\Exports\DeliveryCollapseExport;
use App\Exports\DeliveryFulllExport;
use App\Helpers\LogHelper;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Tag(
 *     name="Agent Delivery Header",
 *     description="API endpoints for managing Delivery Headers and their Details"
 * )
 */
class AgentDeliveryHeaderController extends Controller
{
    public function __construct(protected AgentDeliveryHeaderService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->listDeliveries($perPage);
            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->globalFilter($perPage);

            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function store(AgentDeliveryHeaderRequest $request): JsonResponse
    // {
    //     try {
    //         $header = $this->service->store($request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Delivery created successfully',
    //             'data' => $header
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }
    // public function store(AgentDeliveryHeaderRequest $request): JsonResponse
    // {
    //     try {
    //         $header = $this->service->store($request->validated());

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Delivery created successfully',
    //             'data'    => $header
    //         ], 201);
    //     } catch (Throwable $e) {

    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }


    public function show(string $uuid): JsonResponse
    {
        try {
            $current = $this->service->findByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Delivery fetched successfully',
                'data'    => new AgentDeliveryHeaderResource($current),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Delivery not found for given UUID',
            ], 404);
        }
    }


    public function update(AgentDeliveryHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $header = $this->service->updateByUuid($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery updated successfully',
                'data' => new AgentDeliveryHeaderResource($header)
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportCapsCollection(Request $request)
    {
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';

    $filename = 'caps_collection_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'deliveryheaderexports/' . $filename;

    $filters = $request->input('filter', []);
    $fromDate = $filters['from_date'] ?? null;
    $toDate   = $filters['to_date'] ?? null;
    $export = new DeliveryHeaderExport($fromDate, $toDate, $filters);

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


    public function exportCapsCollectionfull(Request $request)
    {
        $uuid   = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));

        $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');
        $filename  = 'agent_delivery_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path      = 'exports/' . $filename;
        if ($format === 'csv' || $format === 'xlsx') {

            $export = new DeliveryFulllExport($uuid);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
            } else {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
            }
        }
        if ($format === 'pdf') {

            $delivery = AgentDeliveryHeaders::with([
                'warehouse',
                'customer',
                'salesman',
                'route',
                'details.item',
                'details.Uom'
            ])->where('uuid', $uuid)->first();

            if (!$delivery) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Delivery not found.',
                ]);
            }

            $deliveryDetails = $delivery->details;
            $pdf = \PDF::loadView('delivery', [
                'delivery'         => $delivery,
                'deliveryDetails'  => $deliveryDetails
            ])->setPaper('A4');

            \Storage::disk('public')->makeDirectory('exports');
            \Storage::disk('public')->put($path, $pdf->output());
        }
        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportDeliveryCollapse(Request $request)
    {
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';

    $filename = 'collapse_delivery_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'deliveryexports/' . $filename;
    $filterData = $request->input('filter', []);
    $fromDate = $filterData['from_date'] ?? null;
    $toDate   = $filterData['to_date'] ?? null;
    $filters = [
        'company_id'  => $filterData['company_id'] ?? null,
        'region_id'   => $filterData['region_id'] ?? null,
        'route_id'    => $filterData['route_id'] ?? null,
        'salesman_id' => $filterData['salesman_id'] ?? null,
    ];

    $export = new DeliveryCollapseExport($fromDate, $toDate, $filters);

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
}
