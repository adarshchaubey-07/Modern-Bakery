<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class FrigeCustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_name'   => 'nullable|string',
            'owner_name'    => 'nullable|string',
            'contact_number' => 'nullable|string',
            'status'        => 'nullable|integer',
            'fridge_status' => 'nullable|integer',
            'remark'        => 'nullable|string',
        ];
    }
}
