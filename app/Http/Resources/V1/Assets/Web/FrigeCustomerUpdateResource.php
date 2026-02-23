<?php


namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class FrigeCustomerUpdateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // 🔹 Primary
            'id'        => $this->id,
            'uuid'      => $this->uuid,
            'osa_code'  => $this->osa_code,

            // 🔹 Outlet Info
            'outlet_name'   => $this->outlet_name,
            'owner_name'    => $this->owner_name,
            'contact_number' => $this->contact_number,
            'landmark'      => $this->landmark,
            'outlet_type'   => $this->outlet_type,
            'existing_coolers' => $this->existing_coolers,
            'outlet_weekly_sale_volume' => $this->outlet_weekly_sale_volume,
            'display_location' => $this->display_location,
            'chiller_safty_grill' => $this->chiller_safty_grill,

            'warehouse' => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code ?? null,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,
            // 🔹 People / Management
            'agent'                     => $this->agent,
            'manager_sales_marketing'   => $this->manager_sales_marketing,
            'sales_marketing_director'  => $this->sales_marketing_director,
            'area_manager'              => $this->area_manager,
            'sales_excutive'            => $this->sales_excutive,
            'salesman_id'               => $this->salesman_id,

            'salesman' => $this->salesman ? [
                'id'   => $this->salesman->id,
                'code' => $this->salesman->osa_code ?? null,
                'name' => $this->salesman->name ?? null,
            ] : null,
            'route_id'                  => $this->route_id,
            'route' => $this->route ? [
                'id'   => $this->route->id,
                'code' => $this->route->route_code ?? null,
                'name' => $this->route->route_name ?? null,
            ] : null,

            // 🔹 Customer Details
            'customer_name'                 => $this->customer_name,
            'name_contact_of_the_customer'  => $this->name_contact_of_the_customer,
            'postal_address'                => $this->postal_address,
            'location'                      => $this->location,

            // 🔹 Chiller / Asset Details
            'model'                 => $this->model,
            'hil'                   => $this->hil,
            'ir_reference_no'       => $this->ir_reference_no,
            'installation_done_by'  => $this->installation_done_by,
            'date_lnitial'          => $this->date_lnitial,
            'date_lnitial2'         => $this->date_lnitial2,
            'contract_attached'     => $this->contract_attached,
            'machine_number'        => $this->machine_number,
            'brand'                 => $this->brand,
            'asset_number'          => $this->asset_number,
            'serial_no'             => $this->serial_no,
            'chiller_size_requested' => $this->chiller_size_requested,
            'chiller_asset_care_manager' => $this->chiller_asset_care_manager,

            // 🔹 Sales / Business
            'outlet_weekly_sales'         => $this->outlet_weekly_sales,
            'stock_share_with_competitor' => $this->stock_share_with_competitor,
            'specify_if_other_type'       => $this->specify_if_other_type,

            // 🔹 Documents (Text Flags)
            'national_id'          => $this->national_id,
            'outlet_stamp'         => $this->outlet_stamp,
            'lc_letter'            => $this->lc_letter,
            'trading_licence'      => $this->trading_licence,
            'password_photo'       => $this->password_photo,
            'outlet_address_proof' => $this->outlet_address_proof,

            // 🔹 Documents (Files)
            'national_id_file'           => $this->national_id_file,
            'password_photo_file'        => $this->password_photo_file,
            'outlet_address_proof_file'  => $this->outlet_address_proof_file,
            'trading_licence_file'       => $this->trading_licence_file,
            'lc_letter_file'             => $this->lc_letter_file,
            'outlet_stamp_file'          => $this->outlet_stamp_file,
            'sign__customer_file'        => $this->sign__customer_file,

            // 🔹 Second Set Files
            'national_id1_file'           => $this->national_id1_file,
            'password_photo1_file'        => $this->password_photo1_file,
            'outlet_address_proof1_file'  => $this->outlet_address_proof1_file,
            'trading_licence1_file'       => $this->trading_licence1_file,
            'lc_letter1_file'             => $this->lc_letter1_file,
            'outlet_stamp1_file'          => $this->outlet_stamp1_file,

            // 🔹 Signatures / Images
            'sign_salesman_file' => $this->sign_salesman_file,
            'fridge_scan_img'    => $this->fridge_scan_img,

            // 🔹 Office / Approval Meta
            'fridge_office_id'   => $this->fridge_office_id,
            'fridge_maanger_id'  => $this->fridge_maanger_id,
            'agreement_id'       => $this->agreement_id,

            // 🔹 Status
            'status'                  => $this->status,
            'request_document_status' => $this->request_document_status,
            'fridge_status'           => $this->fridge_status,
            'remark'                  => $this->remark,

            // 🔹 Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // ==========================================
            // 🚀 APPROVAL (OLD FLAT FORMAT – FINAL)
            // ==========================================
            'approval_status' => $this->approval_status ?? null,
            'current_step'    => $this->current_step ?? null,
            'request_step_id' => $this->request_step_id ?? null,
            'progress'        => $this->progress ?? null,
        ];
    }
}
