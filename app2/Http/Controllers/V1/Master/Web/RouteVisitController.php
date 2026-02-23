<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitRequest;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitSingleUpdatedRequest;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitUpdateRequest;
use App\Http\Resources\V1\Master\Web\RouteVisitResource;
use App\Services\V1\MasterServices\Web\RouteVisitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\LogHelper;

/**
 * @OA\Tag(
 *     name="RouteVisit",
 *     description="API endpoints for managing route visits"
 * )
 *
 * @OA\Schema(
 *     schema="RouteVisit",
 *     type="object",
 *     required={"customer_type", "customers"},
 *     @OA\Property(
 *         property="customer_type",
 *         type="integer",
 *         description="1 for Agent Customer, 2 for Merchandisor",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="customers",
 *         type="array",
 *         description="List of customers to create route visits for",
 *         @OA\Items(
 *             type="object",
 *             required={"customer_id", "company_id", "from_date", "to_date"},
 *
 *             @OA\Property(property="customer_id", type="integer", example=101),
 *
 *             @OA\Property(
 *                 property="company_id",
 *                 type="string",
 *                 description="Comma-separated list of company IDs",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="region",
 *                 type="string",
 *                 description="Comma-separated list of regions",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="area",
 *                 type="string",
 *                 description="Comma-separated list of areas",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="warehouse",
 *                 type="string",
 *                 description="Comma-separated list of warehouses",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="route",
 *                 type="string",
 *                 description="Comma-separated list of routes",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="days",
 *                 type="string",
 *                 description="Comma-separated list of days",
 *                 example="Monday,Wednesday,Friday"
 *             ),
 *             @OA\Property(
 *                 property="from_date",
 *                 type="string",
 *                 format="date",
 *                 example="2025-10-28"
 *             ),
 *             @OA\Property(
 *                 property="to_date",
 *                 type="string",
 *                 format="date",
 *                 example="2025-11-10"
 *             ),
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 description="0 for inactive, 1 for active",
 *                 example=1
 *             )
 *         )
 *     )
 * )
 */
class RouteVisitController extends Controller
{
    protected $service;

    public function __construct(RouteVisitService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/list",
     *     tags={"RouteVisit"},
     *     summary="Get all route visits with pagination & filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="osa_code", in="query", description="Filter by OSA code", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of route visits",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route visits fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RouteVisit")),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $perPage = request()->get('limit', 50);
        $filters = request()->only(['customer', 'from_date', 'to_date', 'customer_id', 'customer_type', 'status',]);
        $data = $this->service->getAll($perPage, $filters);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Route visits fetched successfully',
            'data' => RouteVisitResource::collection($data),
            'pagination' => [
                'page' => $data->currentPage(),
                'limit' => $data->perPage(),
                'totalPages' => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/route-visits/add",
     *     tags={"RouteVisit"},
     *     summary="Create a new route visit",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
     *     @OA\Response(response=201, description="Route visit created successfully", @OA\JsonContent(ref="#/components/schemas/RouteVisit"))
     * )
     */
public function store(RouteVisitRequest $request): JsonResponse
{
    $validated = $request->validated();
    $result = $this->service->create($validated);
    $isMultiple = is_iterable($result) && count($result) > 1;
    $logCurrentData = $isMultiple
        ? collect($result)->map(fn ($r) => $r->toArray())->toArray()
        : (is_array($result) ? $result[0]->toArray() : $result->toArray());
    LogHelper::store( 
        'master',           
        'route_visit',     
        'add',           
        null,             
        $logCurrentData, 
        auth()->id()        
    );

    return response()->json([
        'status' => 'success',
        'code' => 201,
        'message' => $isMultiple
            ? 'Multiple route visits created successfully'
            : 'Route visit created successfully',
        'data' => $isMultiple
            ? RouteVisitResource::collection($result)
            : new RouteVisitResource(is_array($result) ? $result[0] : $result)
    ], 201);
}


    /**
     * @OA\Get(
     *     path="/api/master/route-visits/{uuid}",
     *     tags={"RouteVisit"},
     *     summary="Get a single route visit by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Route visit details", @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
     *     @OA\Response(response=404, description="Route visit not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $result = $this->service->findByUuid($uuid);

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Route visit not found',
                'data' => null
            ], 404);
        }

        // If service ever returns multiple records (in future bulk logic)
        $isMultiple = is_iterable($result) && count($result) > 1;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $isMultiple
                ? 'Multiple route visits fetched successfully'
                : 'Route visit fetched successfully',
            'data' => $isMultiple
                ? RouteVisitResource::collection($result)
                : new RouteVisitResource(is_array($result) ? $result[0] : $result)
        ], 200);
    }

    // /**
    //  * @OA\Put(
    //  *     path="/api/master/route-visits/update/{uuid}",
    //  *     tags={"RouteVisit"},
    //  *     summary="Update a route visit by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the route visit to update",
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(
    //  *                 property="customer_type",
    //  *                 type="integer",
    //  *                 description="1 for Agent Customer, 2 for Merchandisor",
    //  *                 example=1
    //  *             ),
    //  *             @OA\Property(
    //  *                 property="customers",
    //  *                 type="array",
    //  *                 description="List of customers to update route visits for (without customer_id)",
    //  *                 @OA\Items(
    //  *                     type="object",
    //  *                     @OA\Property(
    //  *                         property="region",
    //  *                         type="array",
    //  *                         @OA\Items(type="integer", example=1)
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="area",
    //  *                         type="array",
    //  *                         @OA\Items(type="integer", example=1)
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="warehouse",
    //  *                         type="array",
    //  *                         @OA\Items(type="integer", example=81)
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="route",
    //  *                         type="array",
    //  *                         @OA\Items(type="integer", example=47)
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="days",
    //  *                         type="array",
    //  *                         @OA\Items(type="string", example="Monday")
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="from_date",
    //  *                         type="string",
    //  *                         format="date",
    //  *                         example="2025-10-28"
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="to_date",
    //  *                         type="string",
    //  *                         format="date",
    //  *                         example="2025-11-10"
    //  *                     ),
    //  *                     @OA\Property(
    //  *                         property="status",
    //  *                         type="integer",
    //  *                         example=1
    //  *                     )
    //  *                 )
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Route visit updated successfully",
    //  *         @OA\JsonContent(ref="#/components/schemas/RouteVisit")
    //  *     )
    //  * )
    //  */
    // public function update(RouteVisitSingleUpdatedRequest $request, string $uuid): JsonResponse
    // {
    //     $result = $this->service->updateByUuid($uuid, $request->validated());

    //     $isMultiple = is_iterable($result) && count($result) > 1;

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => $isMultiple
    //             ? 'Multiple route visits updated successfully'
    //             : 'Route visit updated successfully',
    //         'data' => $isMultiple
    //             ? RouteVisitResource::collection($result)
    //             : new RouteVisitResource(is_array($result) ? $result[0] : $result)
    //     ], 200);
    // }


    // /**
    //  * @OA\Put(
    //  *     path="/api/master/route-visits/update/{uuid}",
    //  *     tags={"RouteVisit"},
    //  *     summary="Update a route visit by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
    //  *     @OA\Response(response=200, description="Route visit updated successfully", @OA\JsonContent(ref="#/components/schemas/RouteVisit"))
    //  * )
    //  */
    // public function update(RouteVisitRequest $request, string $uuid): JsonResponse
    // {
    //     $result = $this->service->updateByUuid($uuid, $request->validated());

    //     $isMultiple = is_iterable($result) && count($result) > 1;

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => $isMultiple
    //             ? 'Multiple route visits updated successfully'
    //             : 'Route visit updated successfully',
    //         'data' => $isMultiple
    //             ? RouteVisitResource::collection($result)
    //             : new RouteVisitResource(is_array($result) ? $result[0] : $result)
    //     ], 200);
    // }

    /**
     * @OA\Put(
     *     path="/api/master/route-visits/bulk-update",
     *     tags={"RouteVisit"},
     *     summary="Bulk update multiple route visits",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"customer_type", "customers"},
     *             @OA\Property(
     *                 property="customer_type",
     *                 type="integer",
     *                 description="1 for Agent Customer, 2 for Merchandisor",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="customers",
     *                 type="array",
     *                 description="List of customers with route visit data to update",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"customer_id", "company_id", "from_date", "to_date"},
     *                     @OA\Property(property="customer_id", type="integer", example=79),
     *
     *                     @OA\Property(
     *                         property="company_id",
     *                         type="string",
     *                         description="Comma-separated list of company IDs",
     *                         example="157,158"
     *                     ),
     *                     @OA\Property(
     *                         property="region",
     *                         type="string",
     *                         description="Comma-separated list of region IDs",
     *                         example="103,104"
     *                     ),
     *                     @OA\Property(
     *                         property="area",
     *                         type="string",
     *                         description="Comma-separated list of area IDs",
     *                         example="44"
     *                     ),
     *                     @OA\Property(
     *                         property="warehouse",
     *                         type="string",
     *                         description="Comma-separated list of warehouse IDs",
     *                         example="119,120"
     *                     ),
     *                     @OA\Property(
     *                         property="route",
     *                         type="string",
     *                         description="Comma-separated list of route IDs",
     *                         example="63,64"
     *                     ),
     *                     @OA\Property(
     *                         property="days",
     *                         type="string",
     *                         description="Comma-separated list of days",
     *                         example="Monday,Wednesday,Friday"
     *                     ),
     *                     @OA\Property(
     *                         property="from_date",
     *                         type="string",
     *                         format="date",
     *                         example="2025-10-28"
     *                     ),
     *                     @OA\Property(
     *                         property="to_date",
     *                         type="string",
     *                         format="date",
     *                         example="2025-11-10"
     *                     ),
     *                     @OA\Property(
     *                         property="status",
     *                         type="integer",
     *                         description="0 for inactive, 1 for active",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route visits updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Multiple route visits updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/RouteVisit")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function bulkUpdate(RouteVisitUpdateRequest $request): JsonResponse
    {
        $result = $this->service->update(null, $request->validated());

        $isMultiple = is_iterable($result) && count($result) > 1;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $isMultiple
                ? 'Multiple route visits updated successfully'
                : 'Route visit updated successfully',
            'data' => RouteVisitResource::collection($result)
        ], 200);
    }




    // public function update(RouteVisitRequest $request, string $uuid): JsonResponse
    // {
    //     $routeVisit = $this->service->updateByUuid($uuid, $request->validated());

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Route visit updated successfully',
    //         'data' => new RouteVisitResource($routeVisit)
    //     ]);
    // }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/master/route-visits/{uuid}",
    //  *     tags={"RouteVisit"},
    //  *     summary="Delete a route visit by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
    //  *     @OA\Response(response=200, description="Route visit deleted successfully", @OA\JsonContent(@OA\Property(property="message", type="string", example="Route visit deleted successfully"))),
    //  *     @OA\Response(response=404, description="Route visit not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     $this->service->deleteByUuid($uuid);

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Route visit deleted successfully'
    //     ]);
    // }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/salesmen",
     *     tags={"RouteVisit"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get salesman list (only sub_type = 1)",
     *     description="This API returns the list of salesmen where sub_type = 1. You can filter by status, search keyword, or apply pagination.",
     *     
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by salesman status (e.g. 1 for active, 0 for inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by salesman name",
     *         required=false,
     *         @OA\Schema(type="string", example="Rohit")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Salesman list fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Salesman list fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="osa_code", type="string", example="OSA001"),
     *                     @OA\Property(property="name", type="string", example="Rohit Sharma")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function salesmanlist(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search', 'per_page']);
            $data = $this->service->getAlll($filters);

            return response()->json([
                'status' => true,
                'message' => 'Merchandisher list fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/customerlist/{merchandiser_id}",
     *     tags={"RouteVisit"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get company customers by merchandiser ID (only id, code, name)",
     *     description="Returns all company customers for a specific merchandiser_id with only id, code, and name fields.",
     *
     *     @OA\Parameter(
     *         name="merchandiser_id",
     *         in="path",
     *         required=true,
     *         description="ID of the merchandiser",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by customer status (1=Active, 0=Inactive)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by business name",
     *         @OA\Schema(type="string", example="Star Traders")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customers fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="OSA001"),
     *                     @OA\Property(property="name", type="string", example="Star Traders")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No customers found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No customers found for this merchandiser ID"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */
    public function getByMerchandiser($merchandiser_id, Request $request)
    {
        try {
            $filters = $request->only(['status', 'search']);
            $data = $this->service->getByMerchandiser($merchandiser_id, $filters);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No customers found for this merchandiser ID',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Customers fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');

        $result = $this->service->export($format);

        if (!$result['status']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 200);
    }

    public function globlesearch(Request $request)
    {
        $keyword = $request->input('query');
        $perPage = $request->input('per_page', 50);

        $visits = $this->service->globalSearch($keyword, $perPage);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Route visits fetched successfully',
            'data'    => RouteVisitResource::collection($visits),
            'pagination' => [
                'current_page' => $visits->currentPage(),
                'last_page'    => $visits->lastPage(),
                'per_page'     => $visits->perPage(),
                'total'        => $visits->total(),
            ]
        ]);
    }
}
