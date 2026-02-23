<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class CallRegisterUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "osa_code"        => "sometimes|string|max:20",
            "ticket_type"     => "sometimes|string|max:20",
            "ticket_date"     => "sometimes|date",
            "technician_id"   => "sometimes|integer",
            "sales_valume"    => "sometimes|nullable|string|max:50",
            "ctc_status"      => "sometimes|integer",

            "chiller_serial_number" => "sometimes|nullable|string|max:50",
            "asset_number"          => "sometimes|nullable|string|max:50",
            "model_number"          => "sometimes|string|max:50",
            "chiller_code"          => "sometimes|nullable|string|max:20",
            "branding"              => "sometimes|nullable|string|max:50",

            "outlet_code"       => "sometimes|nullable|string|max:20",
            "outlet_name"       => "sometimes|nullable|string|max:50",
            "owner_name"        => "sometimes|nullable|string|max:50",
            "road_street"       => "sometimes|nullable|string|max:100",
            "town"              => "sometimes|nullable|string|max:100",
            "landmark"          => "sometimes|nullable|string|max:100",
            "district"          => "sometimes|nullable|string|max:100",

            "contact_no1"       => "sometimes|string|max:50",
            "contact_no2"       => "sometimes|nullable|string|max:50",

            "current_outlet_code"   => "sometimes|nullable|string|max:200",
            "current_outlet_name"   => "sometimes|nullable|string|max:50",
            "current_owner_name"    => "sometimes|nullable|string|max:50",
            "current_road_street"   => "sometimes|nullable|string|max:100",
            "current_town"          => "sometimes|nullable|string|max:100",
            "current_landmark"      => "sometimes|nullable|string|max:100",
            "current_district"      => "sometimes|nullable|string|max:100",
            "current_contact_no1"   => "sometimes|string|max:50",
            "current_contact_no2"   => "sometimes|nullable|string|max:50",

            "current_warehouse" => "sometimes|nullable|string|max:1000",
            "current_asm"       => "sometimes|nullable|string|max:500",
            "current_rm"        => "sometimes|nullable|string|max:500",

            "nature_of_call"    => "sometimes|string|max:500",
            "follow_up_action"  => "sometimes|string|max:500",
            "followup_status"   => "sometimes|nullable|integer",
            "status"            => "sometimes|string|max:10",

            "call_category"        => "sometimes|nullable|string|max:255",
            "reason_for_cancelled" => "sometimes|nullable|string|max:100",

            "customer_id" => "sometimes|nullable|integer",
            "fridge_id"   => "sometimes|nullable|integer",
        ];
    }
}
