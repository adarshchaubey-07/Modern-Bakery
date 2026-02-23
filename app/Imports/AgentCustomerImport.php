<?php

// namespace App\Imports;

// use App\Models\AgentCustomer;
// use Illuminate\Support\Facades\Auth;
// use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;

// class AgentCustomerImport implements ToModel, WithHeadingRow
// {
//     public function model(array $row)
//     {
//         $custGroup = isset($row['cust_group'])
//             ? (int) trim($row['cust_group'])
//             : null;

//         $paymentType = match ($custGroup) {
//             25 => 'cash',
//             35 => 'credit',
//             default => null,
//         };

//         return new AgentCustomer([
//             'id'                => $row['id'] ?? null,
//             'osa_code'          => $row['osa_code'] ?? null,
//             'name'              => $row['name'] ?? null,
//             'contact_no'        => $row['contact_no'] ?? null,
//             'city'              => $row['city'] ?? null,
//             'street'            => $row['street'] ?? null,
//             'customer_type'     => $row['customer_type'] ?? null,
//             'region'            => $row['region'] ?? null,
//             'outlet_channel_id' => 26,
//             'credit_limit'      => $row['credit_limit'] ?? null,
//             'creditday'         => $row['creditday'] ?? null,
//             'cust_group'        => $custGroup,
//             'route_id'          => 575,
//             'account_group'     => $row['account_group'] ?? null,
//             'email'             => $row['email'] ?? null,
//             'tin_no'            => $row['tin_no'] ?? null,
//             'risk_cat'          => $row['risk_cat'] ?? null,
//             'payment_type'      => $paymentType, 
//             'status'            => 1,
//             'created_user'      => Auth::id(),
//             'updated_user'      => Auth::id(),
//         ]);
//     }
// } 
namespace App\Imports;

use App\Models\AgentCustomer;
use App\Models\CustomerCategory;
use App\Models\Region;
use App\Models\AccountGrp;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AgentCustomerImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $custGroup = isset($row['cust_group'])
            ? (int) trim($row['cust_group'])
            : null;

        $paymentType = match ($custGroup) {
            25 => 'cash',
            35 => 'credit',
            default => null,
        };

        $riskCatId = CustomerCategory::where('customer_category_code', trim($row['risk_cat'] ?? ''))
            ->value('id');

        $regionId = Region::where('region_code', trim($row['region'] ?? ''))
            ->value('id');

        $accountGrpId = AccountGrp::where('code', trim($row['account_group'] ?? ''))
            ->value('id');

        return new AgentCustomer([
            'id'                => $row['id'] ?? null,
            'osa_code'          => $row['osa_code'] ?? null,
            'name'              => $row['name'] ?? null,
            'contact_no'        => $row['contact_no'] ?? null,
            'city'              => $row['city'] ?? null,
            'street'            => $row['street'] ?? null,
            'customer_type'     => $row['customer_type'] ?? null,
            'region_id'         => $regionId,
            'outlet_channel_id' => 26,
            'divison'           => $row['division'] ?? null,
            'credit_limit'      => $row['credit_limit'] ?? null,
            'creditday'         => $row['creditday'] ?? null,
            'cust_group'        => $custGroup,
            'route_id'          => 575,
            'account_group'     => $accountGrpId,
            'email'             => $row['email'] ?? null,
            'tin_no'            => $row['tin_no'] ?? null,
            'category_id'       => $riskCatId,
            'payment_type'      => $paymentType,
            'status'            => 1,
            'created_user'      => Auth::id(),
            'updated_user'      => Auth::id(),
        ]);
    }
}

