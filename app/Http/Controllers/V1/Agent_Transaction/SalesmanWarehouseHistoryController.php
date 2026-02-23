<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Agent_Transaction\SalesmanWarehouseHistoryResource;
use App\Services\V1\Agent_Transaction\SalesmanWarehouseHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesmanWarehouseHistoryController extends Controller
{
    protected SalesmanWarehouseHistoryService $service;

    public function __construct(SalesmanWarehouseHistoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Remove pagination keys from filters
            $filters = collect($request->all())->except([
                'page',
                'per_page'
            ])->toArray();

            $perPage = $request->get('per_page', 50);

            $result = $this->service->list($perPage, $filters);

            return response()->json([
                'status'     => 'success',
                'message'    => 'Salesman warehouse history fetched successfully',
                'data'       => SalesmanWarehouseHistoryResource::collection($result->items()),
                'pagination' => [
                    'total'        => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page'     => $result->perPage(),
                    'last_page'    => $result->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
