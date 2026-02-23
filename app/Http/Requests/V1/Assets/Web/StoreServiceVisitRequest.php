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
            'osa_code'   => 'nullable|string|max:50',
            'ticket_type'   => 'required|string|max:50',
            'technician_id' => 'required|integer',
            'ticket_status'   => 'nullable|integer',
            'work_status'   => 'nullable|integer',

            // 🔹 Time & asset info
            'time_in'   => 'nullable|date',
            'time_out'  => 'nullable|date',
            'model_no'  => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'asset_no'  => 'nullable|string|max:100',
            'branding'  => 'nullable|string|max:100',

            // 🔹 Contacts
            'outlet_name'    => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:150',

            // 🔹 Technical readings
            'current_voltage'    => 'nullable|numeric',
            'amps'               => 'nullable|numeric',
            'cabin_temperature'  => 'nullable|numeric',

            // 🔹 Status & comments
            'ct_status'           => 'nullable|integer',
            'cts_comment'         => 'nullable|string',
            'technical_behaviour' => 'nullable|string',
            'service_quality'     => 'nullable|string',

            // 🔹 Machine checks (boolean)
            'is_machine_in_working'         => 'nullable|boolean',
            'cleanliness'                   => 'nullable|boolean',
            'condensor_coil_cleand'         => 'nullable|boolean',
            'gaskets'                       => 'nullable|boolean',
            'light_working'                 => 'nullable|boolean',
            'branding_no'                   => 'nullable|boolean',
            'propper_ventilation_available' => 'nullable|boolean',
            'leveling_positioning'          => 'nullable|boolean',
            'stock_availability_in'         => 'nullable|boolean',

            // 🔹 Files
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

            // 🔹 Other
            'nature_of_call_id' => 'nullable|integer',



            // // 🔹 Basic details
            // 'ticket_type'        => 'required|string|max:50',
            // 'technician_id'      => 'required|integer',
            // 'work_status'        => 'required|string|max:100',

            // // 🔹 Machine checks (boolean-friendly)
            // 'is_machine_in_working'         => 'nullable',
            // 'cleanliness'                   => 'nullable',
            // 'condensor_coil_cleand'         => 'nullable',
            // 'gaskets'                       => 'nullable',
            // 'light_working'                 => 'nullable',
            // 'branding_no'                   => 'nullable',
            // 'propper_ventilation_available' => 'nullable',
            // 'leveling_positioning'          => 'nullable',
            // 'stock_availability_in'         => 'nullable',

            // // 🔹 Files (NOT image-only)
            // 'scan_image'                        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'is_machine_in_working_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'cleanliness_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'condensor_coil_cleand_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'gaskets_img'                       => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'light_working_img'                 => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'branding_no_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'propper_ventilation_available_img' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'leveling_positioning_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'stock_availability_in_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'cooler_image'                      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'cooler_image2'                     => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'type_details_photo1'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'type_details_photo2'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            // 'customer_signature'                => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',

            // // 🔹 Other optional fields
            // 'comment'               => 'nullable|string',
            // 'spare_request'         => 'nullable|string|max:500',
            // 'work_done_type'        => 'nullable|string|max:100',
            // 'nature_of_call_id'     => 'nullable|integer',
        ];
    }
}
