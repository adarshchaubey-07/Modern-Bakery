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
public function store(StoreServiceVisitRequest $request): JsonResponse
    {
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

    public function generateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:5'
        ]);

        $code = $this->service->generateCode($validated['prefix']);

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => [
                'prefix'   => strtoupper($validated['prefix']),
                'osa_code' => $code
            ]
        ]);
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


    public function export(Request $request)
    {
        try {
            $filters = [
                'technician_id' => $request->get('technician_id'),
                'work_status'   => $request->get('work_status'),
                'ticket_type'   => $request->get('ticket_type'),
                'from_date'     => $request->get('from_date'),
                'to_date'       => $request->get('to_date'),
            ];

            $format = strtolower($request->get('format', 'xlsx'));

            if (!in_array($format, ['xlsx', 'csv'])) {
                $format = 'xlsx';
            }

            $filename = 'service_visit_export_' . now()->format('Ymd_His') . '.' . $format;
            $path     = 'exports/service_visit/' . $filename;

            \Storage::disk('public')->makeDirectory('exports/service_visit');

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\ServiceVisitExport($filters),
                $path,
                'public'
            );

            $url = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

            return response()->json([
                'status'       => 'success',
                'message'      => strtoupper($format) . ' export generated successfully',
                'download_url' => $url,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate Service Visit export',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
