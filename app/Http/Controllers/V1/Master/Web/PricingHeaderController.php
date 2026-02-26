<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PricingHeaderRequest;
use App\Http\Resources\V1\Master\Web\PricingHeaderResource;
use App\Http\Resources\V1\Master\Web\PricingDetailResource;
use App\Services\V1\MasterServices\Web\PricingHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Imports\PricingImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PricingHeader;
use App\Models\PricingDetail;
use App\Models\ItemUOM;


/**
 * @OA\Schema(
 *     schema="PricingHeader",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", example="Summer Discount Pricing"),
 *     @OA\Property(property="description", type="string", example="Pricing header for summer discount scheme"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="apply_on", type="integer", example=1, description="Warehouse ID"),
 *     @OA\Property(property="warehouse_id", type="int", example="1"),
 *     @OA\Property(property="item_type", type="integer", example=5),
 *     @OA\Property(property="status", type="integer", example=1, description="0=Inactive, 1=Active")
 * )
 */
class PricingHeaderController extends Controller
{
    private PricingHeaderService $service;

    public function __construct(PricingHeaderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/pricing-headers/list",
     *     tags={"PricingHeader"},
     *     summary="Get paginated list of pricing headers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", description="Number of records per page", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="List of pricing headers",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PricingHeader")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
{
    $perPage = $request->get('limit', 50);
    $filters = $request->except(['page', 'limit']);

    $pricingHeaders = $this->service->getAll($perPage, $filters);

    return response()->json([
        'status' => 'success',
        'code' => 200,
        'data' => PricingHeaderResource::collection($pricingHeaders),
        'pagination' => [
            'page' => $pricingHeaders->currentPage(),
            'limit' => $pricingHeaders->perPage(),
            'hasMorePages' => $pricingHeaders->hasMorePages(),
        ]
    ]);
}


    // /**
    //  * @OA\Post(
    //  *     path="/api/master/pricing-headers/add",
    //  *     tags={"PricingHeader"},
    //  *     summary="Create a new pricing header",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PricingHeader")),
    //  *     @OA\Response(response=201, description="Pricing header created successfully"),
    //  *     @OA\Response(response=422, description="Validation error")
    //  * )
    //  */
    public function store(PricingHeaderRequest $request): JsonResponse
    {
        $pricingHeader = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'data' => new PricingHeaderResource($pricingHeader)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/master/pricing-headers/{uuid}",
     *     tags={"PricingHeader"},
     *     summary="Get single pricing header by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Pricing header details", @OA\JsonContent(ref="#/components/schemas/PricingHeader")),
     *     @OA\Response(response=404, description="Pricing header not found")
     * )
     */
public function show(Request $request, string $uuid): JsonResponse
{
    $pricingHeader = PricingHeader::select(
            'id',
            'uuid',
            'name',
            'code',
            'description',
            'start_date',
            'end_date',
            'apply_on',
            'status',
            'outlet_channel_id',
            'start_date',
            'end_date',
            'customer_id',
            'company_id'
        )
        ->where('uuid', $uuid)
        ->first();

    if (!$pricingHeader) {
        return response()->json([
            'status' => 'error',
            'code' => 404,
            'message' => 'Pricing header not found'
        ], 404);
    }

    $perPage = (int) $request->get('limit', 10);
    $details = PricingDetail::with([
            'item:id,code,name'
        ])
        ->where('header_id', $pricingHeader->id)
        ->paginate($perPage);

    $itemIds = $details->pluck('item_id')->unique()->values();
    $itemUoms = ItemUOM::with('uom:id,name')
        ->whereIn('item_id', $itemIds)
        ->get()
        ->groupBy('item_id');

    $items = $details->pluck('item')
        ->unique('id')
        ->values()
        ->map(function ($item) use ($itemUoms) {

            $uoms = $itemUoms[$item->id] ?? collect();

            return [
                'id'   => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'item_uoms' => [
                    'item_id' => $item->id, 
                    'uom_id'  => $uoms->pluck('uom_id')->values(),
                    'name'    => $uoms->pluck('uom.name')->values(),
                ]
            ];
        });

    return response()->json([
        'status' => 'success',
        'code'   => 200,
        'data'   => [
            'header'  => new PricingHeaderResource($pricingHeader),
            'item'    => $items,
            'details' => PricingDetailResource::collection($details),
        ],
        'pagination' => [
            'current_page' => $details->currentPage(),
            'last_page'    => $details->lastPage(),
            'per_page'     => $details->perPage(),
            'total'        => $details->total(),
        ]
    ]);
}



    // /**
    //  * @OA\Put(
    //  *     path="/api/master/pricing-headers/update/{uuid}",
    //  *     tags={"PricingHeader"},
    //  *     summary="Update an existing pricing header by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PricingHeader")),
    //  *     @OA\Response(response=200, description="Pricing header updated successfully"),
    //  *     @OA\Response(response=404, description="Pricing header not found")
    //  * )
    //  */
    // public function update(PricingHeaderRequest $request, string $uuid): JsonResponse
    // {
    //     $updated = $this->service->updateByUuid($uuid, $request->validated());

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => new PricingHeaderResource($updated)
    //     ]);
    // }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/master/pricing-headers/{uuid}",
    //  *     tags={"PricingHeader"},
    //  *     summary="Soft delete a pricing header by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
    //  *     @OA\Response(response=200, description="Pricing header deleted successfully"),
    //  *     @OA\Response(response=404, description="Pricing header not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     $this->service->deleteByUuid($uuid);

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Pricing header deleted successfully'
    //     ]);
    // }

    /**
     * @OA\Get(
     *     path="/api/master/pricing-headers/generate-code",
     *     tags={"PricingHeader"},
     *     summary="Generate unique pricing header code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Generated unique code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="string", example="PH001")
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        $code = $this->service->generateCode();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'code' => $code
            ]
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/master/pricing-headers/getItemPrice",
     *     tags={"PricingHeader"},
     *     summary="Item price",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Generated unique code",
     *     )
     * )
     */
    public function getItemPrice(Request $request): JsonResponse
    {
        // dd($request);
        $validated = $request->validate([
            'item_id'      => 'required|integer',
            'customer_id'  => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'route_id'     => 'nullable|integer',
        ]);

        $price = $this->service->findItemPrice(
            $validated['item_id'],
            $validated['customer_id'] ?? null,
            $validated['route_id'] ?? null,
            $validated['warehouse_id'] ?? null
        );

        if (!$price) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Pricing not found for the combination',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Item price found successfully',
            'data'    => [
                'item_id' => $validated['item_id'],
                'ctn_price'   => $price->buom_ctn_price,
                'pc_price' => $price->auom_pc_price
            ]
        ]);
    }

    public function importPricing(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls'
    ]);

    $abcd = Excel::import(new PricingImport, $request->file('file'));
    return response()->json([
        'status' => 'success',
        'message' => 'Pricing imported successfully'
    ]);
}
}
