<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SapLoadFetcher
{
    public function fetch(string $date): array
    {
        $url = config('services.sap.load_url');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Cookie' => 'sap-usercontext=sap-client=400',
        ])->get($url, [
            '$filter' => "IDate eq '{$date}'",
            '$expand' => 'LoadDeliveryItmSet'
        ]);

        if (!$response->successful()) {
            throw new \Exception('SAP API failed');
        }

        return $this->formatSapResponse($response->json());
    }

    private function formatSapResponse(array $response): array
    {
        $data = [];

        foreach ($response['d']['results'] ?? [] as $row) {
            $data[] = [
                'DeliveryNo' => $row['DeliveryNo'],
                'Items'      => $row['LoadDeliveryItmSet']['results'] ?? [],
            ];
        }

        return $data;
    }
}
