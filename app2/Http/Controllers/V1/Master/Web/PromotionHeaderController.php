<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PromotionHeaderRequest;
use App\Http\Requests\V1\MasterRequests\Web\PromotioUpdateRequest;
use App\Http\Resources\V1\Master\Web\PromotionHeaderResource;
use App\Http\Resources\V1\Master\Web\PromotionDataResource;
use App\Services\V1\MasterServices\Web\PromotionHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionHeaderController extends Controller
{
    protected PromotionHeaderService $service;

    public function __construct(PromotionHeaderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/promotion-headers/list",
     *     tags={"Promotions"},
     *     summary="List promotions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="promtion_name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Promotion list",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['id', 'promtion_name', 'status', 'limit']);
            $promotionHeaders = $this->service->list($filters);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion headers retrieved successfully',
                'data'    => PromotionHeaderResource::collection($promotionHeaders),
                'pagination' => [
                    'page'         => $promotionHeaders->currentPage(),
                    'limit'        => $promotionHeaders->perPage(),
                    'totalPages'   => $promotionHeaders->lastPage(),
                    'totalRecords' => $promotionHeaders->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/master/promotion-headers/create",
     *     tags={"Promotions"},
     *     summary="Create promotion",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"promotion_name","promotion_type","from_date","to_date","status","promotion_details"},
     *             @OA\Property(property="promotion_name", type="string"),
     *             @OA\Property(property="promotion_type", type="string"),
     *             @OA\Property(property="bundle_combination", type="string"),
     *             @OA\Property(property="from_date", type="string", format="date"),
     *             @OA\Property(property="to_date", type="string", format="date"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="location", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="customer", type="array", @OA\Items(type="string")),
     *             @OA\Property(
     *                 property="offer_items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="item_id", type="string"),
     *                     @OA\Property(property="uom", type="string")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="promotion_details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="from_qty", type="integer"),
     *                     @OA\Property(property="to_qty", type="integer"),
     *                     @OA\Property(property="free_qty", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(PromotionHeaderRequest $request): JsonResponse
    {
        try {
            $promotionHeader = $this->service->create($request->validated());

            return response()->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Promotion Header and Details created successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/promotion-headers/show/{uuid}",
     *     tags={"Promotions"},
     *     summary="Show promotion by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Promotion detail")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $promotionHeader = $this->service->show($uuid);

            if (!$promotionHeader) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Promotion header not found'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion header retrieved successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/master/promotion-headers/{uuid}",
     *     tags={"Promotions"},
     *     summary="Update promotion by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(PromotioUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $promotionHeader = $this->service->update($uuid, $request->validated());

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion header updated successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getByWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        if (!$warehouseId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'warehouse_id is required'
            ], 422);
        }

        $data = $this->service->getByWarehouseId((int) $warehouseId);

        return response()->json([
            'status' => 'success',
            'count'  => $data->count(),
            'data'   => PromotionDataResource::collection($data)
        ]);
    }
}
