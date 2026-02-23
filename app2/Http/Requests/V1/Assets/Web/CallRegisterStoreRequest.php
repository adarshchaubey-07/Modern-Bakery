<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class CallRegisterStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "osa_code"        => "required|string|max:20",
            "ticket_type"        => "required|string|max:20",
            "ticket_date"        => "required|date",
            "technician_id"      => "required|integer",
            "sales_valume"       => "nullable|string|max:50",
            "ctc_status"         => "required|integer",

            "chiller_serial_number" => "nullable|string|max:50",
            "asset_number"          => "nullable|string|max:50",
            "model_number"          => "required|string|max:50",
            "chiller_code"          => "nullable|string|max:20",
            "branding"              => "nullable|string|max:50",

            "outlet_code"       => "nullable|string|max:20",
            "outlet_name"       => "nullable|string|max:50",
            "owner_name"        => "nullable|string|max:50",
            "road_street"       => "nullable|string|max:100",
            "town"              => "nullable|string|max:100",
            "landmark"          => "nullable|string|max:100",
            "district"          => "nullable|string|max:100",

            "contact_no1"       => "required|string|max:50",
            "contact_no2"       => "nullable|string|max:50",

            "current_outlet_code"   => "nullable|string|max:200",
            "current_outlet_name"   => "nullable|string|max:50",
            "current_owner_name"    => "nullable|string|max:50",
            "current_road_street"   => "nullable|string|max:100",
            "current_town"          => "nullable|string|max:100",
            "current_landmark"      => "nullable|string|max:100",
            "current_district"      => "nullable|string|max:100",
            "current_contact_no1"   => "required|string|max:50",
            "current_contact_no2"   => "nullable|string|max:50",

            "current_warehouse" => "nullable|string|max:1000",
            "current_asm"       => "nullable|string|max:500",
            "current_rm"        => "nullable|string|max:500",

            "nature_of_call"    => "required|string|max:500",
            "follow_up_action"  => "required|string|max:500",
            "followup_status"   => "nullable|integer",
            "status"            => "required|string|max:10",

            "call_category"         => "nullable|string|max:255",
            "reason_for_cancelled"  => "nullable|string|max:100",

            "customer_id"      => "nullable|integer",
            "fridge_id"        => "nullable|integer"
        ];
    }
}
