<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\StoreServiceVisitRequest;
use App\Http\Resources\V1\Assets\Web\ServiceVisitResource;
use App\Services\V1\Assets\Web\ServiceVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ServiceVisitController extends Controller
{
    protected ServiceVisitService $service;

    public function __construct(ServiceVisitService $service)
    {
        $this->service = $service;
    }


    /**
     * List records with filters & pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('per_page', 50);
            $dropdown = $request->boolean('dropdown', false);

            // ✅ REMOVE pagination & control params from filters
            $filters = collect($request->all())->except([
                'page',
                'per_page',
                'dropdown'
            ])->toArray();

            $records = $this->service->getAll($perPage, $filters, $dropdown);

            // ✅ Dropdown response (no pagination)
            if ($dropdown) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Service visits fetched successfully',
                    'data'    => ServiceVisitResource::collection($records),
                ], 200);
            }

            // ✅ Paginated response
            return response()->json([
                'status'  => 'success',
                'message' => 'Service visits fetched successfully',
                'data'    => ServiceVisitResource::collection($records->items()),
                'pagination' => [
                    'total'        => $records->total(),
                    'current_page' => $records->currentPage(),
                    'per_page'     => $records->perPage(),
                    'last_page'    => $records->lastPage(),
                ]
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch service visits',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Create new Service Visit
     */
    public function store(StoreServiceVisitRequest $request): JsonResponse
    {
        // dd($request);
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Service visit created successfully',
                'data'    => new ServiceVisitResource($record),
            ], 201);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create service visit',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $record = $this->service->findByUuid($uuid);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Record not found for UUID: {$uuid}",
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Record fetched successfully',
                'data'    => new ServiceVisitResource($record),
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update by UUID
     */
    public function update(StoreServiceVisitRequest $request, string $uuid): JsonResponse
    {
        try {
            $record = $this->service->updateByUuid($uuid, $request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Service visit updated successfully',
                'data'    => new ServiceVisitResource($record),
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update service visit',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete by UUID
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Service visit deleted successfully',
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete service visit',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
