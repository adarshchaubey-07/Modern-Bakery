<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PricingSourceResolver
{
    public static function fetch(string $source): array
    {
        // Local JSON file
        if (str_ends_with($source, '.json')) {

            if (!file_exists($source)) {
                throw new \Exception("Pricing file not found: {$source}");
            }

            $json = json_decode(file_get_contents($source), true);

            return $json['d']['results'] ?? [];
        }

        // SAP URL
        $response = Http::timeout(60)->get($source);

        if (!$response->successful()) {
            throw new \Exception("SAP request failed: {$source}");
        }

        return $response->json('d.results') ?? [];
    }
}
