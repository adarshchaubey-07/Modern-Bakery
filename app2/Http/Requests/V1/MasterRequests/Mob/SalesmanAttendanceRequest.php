<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanAttendanceRequest extends FormRequest
{
     public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'salesman_id'   => 'required|integer',
            'route_id'      => 'nullable|integer',
            'warehouse_id'  => 'nullable|integer',
            'attendance_date' => 'required|date',
            'time_in'       => 'nullable|date_format:Y-m-d H:i:s',
            'latitude_in'   => 'nullable|numeric',
            'longitude_in'  => 'nullable|numeric',
            'in_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'time_out'      => 'nullable|date_format:Y-m-d H:i:s',
            'latitude_out'  => 'nullable|numeric',
            'longitude_out' => 'nullable|numeric',
            'out_img'       => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'check_in'      => 'nullable|in:true,false,1,0',
            'check_out'     => 'nullable|boolean',
        ];
    }
}