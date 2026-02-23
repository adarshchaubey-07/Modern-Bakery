<?php

namespace App\Http\Controllers;

use App\Services\PricingImportManager;
use Illuminate\Http\JsonResponse;

class PricingImportController extends Controller
{
    protected PricingImportManager $manager;

    public function __construct(PricingImportManager $manager)
    {
        $this->manager = $manager;
    }

    public function importLocal(): JsonResponse
    {
        $sources = [
            storage_path('app/pricing/pricing_channel_10.json'),
            storage_path('app/pricing/pricing_channel_11.json'),
        ];

        $this->manager->importFromSources($sources);

        return response()->json([
            'status'  => true,
            'message' => 'Local pricing import completed'
        ]);
    }

    public function importFromSap(): JsonResponse
    {
        $sources = [
            'http://172.16.0.144:8000/sap/opu/odata/sap/ZSFA_MB_DOWNLOAD_SRV/PricingYrspSet?sap-client=400&$filter=DriverNo%20eq%20%2711078%27',
            'http://172.16.0.144:8000/sap/opu/odata/sap/ZSFA_MB_DOWNLOAD_SRV/PricingYwspSet?sap-client=400&$filter=DriverNo%20eq%20%2711078%27',
        ];

        $this->manager->importFromSources($sources);

        return response()->json([
            'status'  => true,
            'message' => 'SAP pricing import completed'
        ]);
    }
}
