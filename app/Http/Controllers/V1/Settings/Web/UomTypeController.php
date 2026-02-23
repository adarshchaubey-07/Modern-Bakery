<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Services\V1\Settings\Web\UomTypeService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;  

class UomTypeController extends Controller
{
    protected UomTypeService $service;

    public function __construct(UomTypeService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->service->getList();

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'UOM types fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to fetch UOM types',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}