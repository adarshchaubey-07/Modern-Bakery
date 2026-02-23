<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Services\V1\Settings\Web\AccountGrpService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountGrpController extends Controller
{
    protected AccountGrpService $service;

    public function __construct(AccountGrpService $service)
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
            'message'    => 'Advance payments fetched successfully',
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
            'message' => 'Failed to fetch Account grp',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'   => 'required|string|max:20|unique:account_grp,code',
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
                'message' => 'Account group created successfully',
                'data'    => $data,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to create Account group',
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
                    'message' => 'Account group not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Account group fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to fetch Account group',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'   => 'required|string|max:20|unique:account_grp,code,' . $uuid . ',uuid',
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
                    'message' => 'Account group not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Account group updated successfully',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to update Account group',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}