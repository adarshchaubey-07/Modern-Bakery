<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\AgentCustomer;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerCategory;
use App\Models\Region;
use App\Models\OutletChannel;
use App\Models\AccountGrp;
use Illuminate\Support\Facades\Auth;

class SapCustomerService
{
    // public function syncCustomersFromSap(): array
    // {
    //   $driverNo = 10417;
 
    // $url = 'http://172.16.0.144:8000/sap/opu/odata/sap/ZSFA_MB_DOWNLOAD_SRV/CustomerHeaderSet';

    //     $response = Http::withHeaders([
    //         'Accept'           => 'application/json',
    //         'Content-Type'     => 'application/json',
    //         'X-Requested-With' => 'application/json',
    //         'Cookie'           => 'sap-usercontext=sap-client=400',
    //     ])
    //     ->timeout(60) 
    //     ->get($url, [
    //         '$filter' => "DriverNo eq '{$driverNo}'",
    //         '$expand' => 'CustomerSalesAreas,CustomerOpenItems,CustomerCredit,CustomerFlags',
    //     ]);

    //     if ($response->failed()) {
    //         Log::error('SAP Customer API failed', [
    //             'status' => $response->status(),
    //             'body'   => $response->body(),
    //         ]);

    //         throw new \Exception('Failed to fetch data from SAP');
    //     }


    //     $return = $response->json();
    //     dd($return);


    //     // $url = "http://172.16.0.144:8000/sap/opu/odata/sap/ZSFA_MB_DOWNLOAD_SRV/CustomerHeaderSet";
        
    //     // $response = Http::withHeaders([
    //     //     'Accept' => 'application/json',
    //     // ])->get($url, [
    //     //     '$filter' => "DriverNo eq '10417'",
    //     //     '$expand' => 'CustomerSalesAreas,CustomerOpenItems,CustomerCredit,CustomerFlags'
    //     // ]);

    //     // if (!$response->successful()) {
    //     //     throw new \Exception('SAP API failed');
    //     // }

    //     $customers = $response->json('d.results') ?? [];

    //     DB::beginTransaction();
    //     try {
    //         foreach ($customers as $sapCustomer) {
    //             $this->storeOrUpdateCustomer($sapCustomer);
    //         }

    //         DB::commit();
    //         return [
    //             'status' => true,
    //             'count'  => count($customers)
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }
    public function syncCustomersFromSap(bool $fromFile = false): array
{
    if ($fromFile) {
        $path = storage_path('app/customers_10417.json');

        if (!file_exists($path)) {
            throw new \Exception('SAP mock file not found at: ' . $path);
        }

        $data = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in SAP mock file');
        }
    } else {
        $data = $this->fetchFromSap();
    }

    $customers = $data['d']['results'] ?? [];

    if (empty($customers)) {
        throw new \Exception('No customers found to import');
    }

    DB::beginTransaction();
    try {
        foreach ($customers as $sapCustomer) {
            $this->storeOrUpdateCustomer($sapCustomer);
        }

        DB::commit();

        return [
            'status' => true,
            'count'  => count($customers)
        ];
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
private function fetchFromSap(): array
{
    $driverNo = 10417;
    $url = config('services.sap.http://172.16.0.144:8000/sap/opu/odata/sap/ZSFA_MB_DOWNLOAD_SRV/CustomerHeaderSet?$filter=DriverNo%20eq%20%2710417%27&$expand=CustomerSalesAreas,CustomerOpenItems,CustomerCredit,CustomerFlags');

    if (!$url) {
        throw new \Exception('SAP customer URL is not configured');
    }

    $response = Http::withHeaders([
        'Accept' => 'application/json',
        'Cookie' => 'sap-usercontext=sap-client=400',
    ])
    ->timeout(60)
    ->get($url, [
        '$filter' => "DriverNo eq '{$driverNo}'",
        '$expand' => 'CustomerSalesAreas,CustomerOpenItems,CustomerCredit,CustomerFlags',
    ]);

    if ($response->failed()) {
        throw new \Exception('SAP API failed with status ' . $response->status());
    }

    return $response->json();
}
   private function storeOrUpdateCustomer(array $sap)
{
    $salesArea = $sap['CustomerSalesAreas']['results'][0] ?? [];
    $credit    = $sap['CustomerCredit']['results'][0] ?? [];

    $custGroup = isset($salesArea['CustomerGroup1'])
        ? (int) $salesArea['CustomerGroup1']
        : null;

    $paymentType = match ($custGroup) {
        25 => 'cash',
        35 => 'credit',
        default => null,
    };

    $riskCatId = CustomerCategory::where(
        'customer_category_code',
        trim($credit['RiskCat'] ?? '')
    )->value('id');

    $channelId = OutletChannel::where(
        'outlet_channel_code',
        trim($salesArea['DistChannel'] ?? '')
    )->value('id');

    $regionId = Region::where(
        'region_code',
        trim($sap['Regio'] ?? '')
    )->value('id');

    $accountGrpId = AccountGrp::where(
        'code',
        trim($sap['account'] ?? '')
    )->value('id');

    $isDriver = isset($sap['IsDriver']) && strtoupper(trim($sap['IsDriver'])) === 'X'
        ? 1
        : 0;

    AgentCustomer::updateOrCreate(
        ['osa_code' => $sap['CustNo']],
        [
            'name'               => $sap['Name1'] ?? null,
            'contact_no'         => $sap['PhoneNumber'] ?? null,
            'city'               => $sap['City'] ?? null,
            'street'             => $sap['Street'] ?? null,
            'customer_type'      => $sap['Terms'] ?? null,
            'divison'           => $salesArea['Division'] ?? null,

            'region_id'          => $regionId, 
            'category_id'        => $riskCatId,
            'account_group'      => $accountGrpId,

            'cust_group'         => $custGroup,
            'payment_type'       => $paymentType,

            'credit_limit'       => $credit['CreditLimit'] ?? null,
            'creditday'          => $credit['Days'] ?? null,
            'outlet_channel_id'  => $channelId,
            'route_id'           => 575,
            'status'             => 1,

            'tin_no'             => $sap['VAT'] ?? null,
            'email'              => $sap['mail'] ?? null,
            'is_driver'          => $isDriver,

            'created_user'       => Auth::id(),
            'updated_user'       => Auth::id(),
        ]
    );
}


    // private function storeOrUpdateCustomer(array $sap)
    // {
    //     $paymentType = null;

    //     if (($sap['CustomerGroup1'] ?? null) == 35) {
    //         $paymentType = 'credit';
    //     } elseif (($sap['CustomerGroup1'] ?? null) == 25) {
    //         $paymentType = 'cash';
    //     }

    //     AgentCustomer::updateOrCreate(
    //         [
    //             'osa_code' => $sap['CustNo'],
    //         ],
    //         [
    //             'name'               => $sap['Name1'] ?? null,
    //             'city'               => $sap['City'] ?? null,
    //             'region'             => $sap['Regio'] ?? null,
    //             'contact_no'         => $sap['PhoneNumber'] ?? null,
    //             'street'             => $sap['Street'] ?? null,
    //             'customer_type'      => $sap['Terms'] ?? null,
    //             'tin_no'             => $sap['VAT'] ?? null,
    //             'is_driver'          => $sap['IsDriver'] ?? 0,
    //             'email'              => $sap['mail'] ?? null,
    //             'outlet_channel_id'  => $sap['DistChannel'] ?? null,
    //             'cust_group'         => $sap['CustomerGroup1'] ?? null,
    //             'payment_type'       => $paymentType,
    //             'risk_cat'           => $sap['RiskCat'] ?? null,
    //             'creditday'          => $sap['Days'] ?? null,
    //             'account_group'      => $sap['account'] ?? null,
    //             'credit_limit'       => $sap['CreditLimit'] ?? null,
    //             'route_id'           => 575,
    //         ]
    //     );
    // }
}