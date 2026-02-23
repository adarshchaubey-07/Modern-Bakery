<?php

namespace App\Http\Resources\V1\Assets\Web;

use App\Models\Vendor;
use Illuminate\Http\Resources\Json\JsonResource;

class ChillerResource extends JsonResource
{
    public function toArray($request)
    {
        // Get vendor IDs as array
        // $vendorIds = $this->vender_details
        //     ? explode(',', $this->vender_details)
        //     : [];

        // // Fetch vendor details
        // $vendors = Vendor::whereIn('id', $vendorIds)->get(['id', 'code', 'name']);

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'   => $this->osa_code,
            'sap_code'      => $this->sap_code,
            'serial_number' => $this->serial_number,
            'acquisition'   => $this->acquisition,
            'assets_type'    => $this->assets_type,
            'country' => $this->country ? [
                'id' => $this->country->id,
                'code' => $this->country->country_code ?? null,
                'name' => $this->country->country_name ?? null,
            ] : null,
            'vendor' => $this->vendor ? [
                'id' => $this->vendor->id,
                'code' => $this->vendor->code ?? null,
                'name' => $this->vendor->name ?? null,
            ] : null,
            'assets_category' => $this->assetsCategory ? [
                'id' => $this->assetsCategory->id,
                'code' => $this->assetsCategory->osa_code ?? null,
                'name' => $this->assetsCategory->name ?? null,
            ] : null,
            'model_number' => $this->modelNumber ? [
                'id' => $this->modelNumber->id,
                'code' => $this->modelNumber->code ?? null,
                'name' => $this->modelNumber->name ?? null,
            ] : null,
            'manufacturer' => $this->manufacture ? [
                'id' => $this->manufacture->id,
                'code' => $this->manufacture->osa_code ?? null,
                'name' => $this->manufacture->name ?? null,
            ] : null,
            'branding' => $this->brand ? [
                'id' => $this->brand->id,
                'code' => $this->brand->osa_code ?? null,
                'name' => $this->brand->name ?? null,
            ] : null,
            'status' => $this->fridgeStatus ? [
                'id' => $this->fridgeStatus->id ?? null,
                'name' => $this->fridgeStatus->name ?? null,
            ] : null,
            // 'status'        => $this->status,
            'remarks'     => $this->remarks,
            'trading_partner_number'  => $this->trading_partner_number,
            'capacity' => $this->capacity,
            'manufacturing_year'   => $this->manufacturing_year,
        ];
    }
}
