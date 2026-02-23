<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class IROHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // ========== HEADER FIELDS ==========
            'osa_code'       => 'sometimes|string|max:20|unique:tbl_IRO_headers,osa_code,' . $this->id,
            'uuid'           => 'sometimes|uuid',
            // 'status'         => 'required|integer|in:0,1,2,3,4,5,6',   

            // ========== DETAIL FIELDS ==========
            // 'customer_id'    => 'required|integer|exists:agent_customers,id',
            'crf_id'             => 'required|string|exists:chiller_requests,id', // CRF ID (chiller request ID)

            // extra fields (optional, ignore if not used in service)
            // 'name'           => 'nullable|string|max:50',
        ];
    }
}
