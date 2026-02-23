<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Services\V1\Settings\Web\SalesmanRoleService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesmanRoleController extends Controller
{
    protected SalesmanRoleService $service;

    public function __construct(SalesmanRoleService $service)
    {
        $this->service = $service;
    } 

    public function index(): JsonResponse
{
    try {
        $perPage = request()->get('limit', 50);

        $data = $this->service->getList($perPage);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Salesman role fetched successfully',
            'data'       => $data->items(),
            'pagination' => [
                'currentPage' => $data->currentPage(),
                'perPage'     => $data->perPage(),
                'lastPage'    => $data->lastPage(),
                'total'       => $data->total(),
            ]
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to fetch Salesman role',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'   => 'required|string|max:20|unique:salesman_roles,code',
            'name'   => 'required|string|max:100',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->service->create($request->all());

            return response()->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Salesman role created successfully',
                'data'    => $data,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to create Salesman role',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function show(string $uuid): JsonResponse
    {
        try {
            $data = $this->service->findByUuid($uuid);

            if (!$data) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Salesman role not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Salesman role fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to fetch Salesman role',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'   => 'required|string|max:20|unique:salesman_roles,code,' . $uuid . ',uuid',
            'name'   => 'required|string|max:100',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->service->updateByUuid($uuid, $request->all());

            if (!$data) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Salesman role not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Salesman role updated successfully',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to update Salesman role',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}