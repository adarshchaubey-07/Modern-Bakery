<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'warehouse_id' => $this->warehouse_id === "" ? null : $this->warehouse_id,
            'vehicle_id'   => $this->vehicle_id === "" ? null : $this->vehicle_id,
            'route_type'   => isset($this->route_type) ? $this->route_type : null,
            'route_type'   => isset($this->route_type) ? $this->route_type : null,
            'status'       => isset($this->status) ? (int) $this->status : null,
        ]);
    }

    public function rules(): array
    {
        $routeId = $this->route('route')?->id ?? null;

        return [
            'route_code'   => 'nullable|string',
            'route_name'   => 'required|string|max:50',
            'description'  => 'nullable|string',
            'region_id'    => 'required|integer|exists:tbl_region,id',
            'route_type'   => 'required|integer', 
            'route_type.*' => 'required|string|exists:route_types,id',
            'vehicle_id'   => 'nullable|integer',
            'status'       => 'nullable|integer|in:0,1',
        ];
    }
}