<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SapLoadFetcher;
use App\Services\LoadImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SapLoadImportController extends Controller
{
    public function importFromSap(Request $request): JsonResponse
    {
        try {
            $date = $request->get('date', now()->format('Ymd'));

            $fetcher = app(SapLoadFetcher::class);
            $service = app(LoadImportService::class);

            $data = $fetcher->fetch($date);
            $service->import($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Load data imported successfully',
                'date'    => $date,
                'count'   => count($data),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

   public function importFromJson(Request $request): JsonResponse
{
    try {
        $path = storage_path('app/loadimport.json');

        if (!file_exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'JSON file not found'
            ], 404);
        }

        $json = json_decode(file_get_contents($path), true);
        $formattedData = [];

        foreach ($json['d']['results'] ?? [] as $row) {

            if (empty($row['DeliveryNo'])) {
                continue;
            }

            $formattedData[] = [
                'DeliveryNo' => $row['DeliveryNo'],
                'Items' => $row['LoadDelItems']['results'] ?? [],
            ];
        }

        if (empty($formattedData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid delivery records found in JSON'
            ], 422);
        }

        app(\App\Services\LoadImportService::class)->import($formattedData);

        return response()->json([
            'status'  => 'success',
            'message' => 'JSON data imported successfully',
            'count'   => count($formattedData),
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}

}
