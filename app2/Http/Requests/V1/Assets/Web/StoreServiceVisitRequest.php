<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // 🔹 Basic details
            'ticket_type'        => 'required|string|max:50',
            'technician_id'      => 'required|integer',
            'work_status'        => 'required|string|max:100',

            // 🔹 Machine checks (boolean-friendly)
            'is_machine_in_working'         => 'nullable|boolean',
            'cleanliness'                   => 'nullable|boolean',
            'condensor_coil_cleand'         => 'nullable|boolean',
            'gaskets'                       => 'nullable|boolean',
            'light_working'                 => 'nullable|boolean',
            'branding_no'                   => 'nullable|boolean',
            'propper_ventilation_available' => 'nullable|boolean',
            'leveling_positioning'          => 'nullable|boolean',
            'stock_availability_in'         => 'nullable|boolean',

            // 🔹 Files (NOT image-only)
            'scan_image'                        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'is_machine_in_working_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cleanliness_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'condensor_coil_cleand_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'gaskets_img'                       => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'light_working_img'                 => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'branding_no_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'propper_ventilation_available_img' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'leveling_positioning_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'stock_availability_in_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cooler_image'                      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cooler_image2'                     => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'type_details_photo1'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'type_details_photo2'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'customer_signature'                => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',

            // 🔹 Other optional fields
            'comment'               => 'nullable|string',
            'spare_request'         => 'nullable|string|max:500',
            'work_done_type'        => 'nullable|string|max:100',
            'nature_of_call_id'     => 'nullable|integer',
        ];
    }
}
