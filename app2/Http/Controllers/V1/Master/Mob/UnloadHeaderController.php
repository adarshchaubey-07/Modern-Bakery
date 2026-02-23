<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Mob\UnloadHeaderRequest;
// use App\Http\Requests\V1\Agent_Transaction\UnloadHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\UnloadHeaderResource;
use App\Services\V1\MasterServices\Mob\UnloadHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UnloadHeaderFullExport;
use App\Exports\UnloadFullExport;
use App\Exports\UnloadCollapseExport;

/**
 * @OA\Tag(
 *     name="Salesman Unload Header",
 *     description="API endpoints for managing Unload Headers and their Details"
 * )
 */
class UnloadHeaderController extends Controller
{
    public function __construct(protected UnloadHeaderService $service) {}

    /**
     * @OA\Get(
     *     path="/mob/master_mob/unload/list",
     *     tags={"Mob Unload Header"},
     *     summary="List all unloads with pagination and filters",
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="route_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of unloads fetched successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'warehouse_id',
                'route_id',
                'salesman_id',
                // 'region_id',
                'start_date',
                'end_date',
                'status'
            ]);

            $headers = $this->service->all(50, $filters);

            return ResponseHelper::paginatedResponse(
                'Unloads fetched successfully',
                UnloadHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/mob/master_mob/unload/add",
     *     tags={"Mob Unload Header"},
     *     summary="Create a new Unload with details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"warehouse_id","route_id","salesman_id","details"},
     *             @OA\Property(property="warehouse_id", type="integer", example=112),
     *             @OA\Property(property="route_id", type="integer", example=60),
     *             @OA\Property(property="salesman_id", type="integer", example=133),
     *             @OA\Property(property="latitude", type="string", example="28.6139"),
     *             @OA\Property(property="longtitude", type="string", example="77.2090"),
     *             @OA\Property(property="unload_from", type="string", example="salesman"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom","qty"},
     *                     @OA\Property(property="item_id", type="integer", example=45),
     *                     @OA\Property(property="uom", type="integer", example=7),
     *                     @OA\Property(property="qty", type="number", example=25.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Unload created successfully")
     * )
     */
    public function store(UnloadHeaderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $header = $this->service->store($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Unload created successfully',
                'data'    => new UnloadHeaderResource($header)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}