<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('id');
        return [
            'osa_code' => 'required|string|max:200|unique:agent_customers,osa_code', 
            'name' => 'required|string|max:255',
            'customer_type' => 'required|string|max:255',
            'owner_name'=>'required|string|max:25',
            'route_id' => 'required|exists:tbl_route,id',
            'landmark'=>'nullable',
            'district'=>'nullable',
            'street'=>'required',
            'city'=>'required',
            'town'=>'nullable',
            'whatsapp_no' => 'nullable|string|max:200',
            'contact_no' => 'required|string|max:20',
            'contact_no2' => 'required|string|max:20',
            'buyertype' => 'required|in:0,1',
            'payment_type' => 'required|string|in:cash,credit',
            'is_cash'=>'integer|in:0,1',
            'vat_no'=>'nullable',
            'is_cash'=>'required|in:0,1',
            'creditday' => 'nullable|numeric',
            'credit_limit'=>'nullable',
            'outlet_channel_id' => 'required|exists:outlet_channel,id',
            'category_id' => 'required|exists:customer_categories,id',
            'subcategory_id' => 'required|exists:customer_sub_categories,id',
            'latitude'=>'nullable|string',
            'longitude'=>'nullable|string',
            'qr_code'=>'nullable|string',
            'status'=>'required|integer|in:0,1',
            'enable_promotion'=>'required|integer|in:0,1',
            'account_group' => 'required|exists:account_grp,id',
            'is_driver' => 'required|integer|in:0,1',
            'tin_no' => 'required',
            'region_id' => 'required|exists:tbl_region,id',
            'cust_group' => 'nullable',

        ];
    }
}
