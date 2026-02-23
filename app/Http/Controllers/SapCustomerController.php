<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SapCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;  

class SapCustomerController extends Controller
{
    // public function sync(SapCustomerService $service): JsonResponse
    // {
    //     try {
    //         $result = $service->syncCustomersFromSap();

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Customers synced successfully',
    //             'data'    => $result
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function sync(Request $request, SapCustomerService $service): JsonResponse
{
    $fromFile = $request->boolean('from_file', false);

    try {
        $result = $service->syncCustomersFromSap($fromFile);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customers synced successfully',
            'data'    => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}
