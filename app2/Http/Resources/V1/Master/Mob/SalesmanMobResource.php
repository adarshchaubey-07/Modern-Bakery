<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanMobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
       return [
            'id'       => $this->id,
            'osa_code' => $this->osa_code,
            // 'username' => $this->username,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'name'     => $this->name ?? null,
            'email'    => $this->email ?? null,
            'contact_no'   => $this->contact_no ?? null,
            'device_no'   => $this->device_no ?? null,
            'route' => [
                'id' => $this-> route_id,
                'name'=> $this -> route->route_name ?? null,
            ],
            'block_date_to'   => $this->block_date_to ?? null,
            'block_date_from'   => $this->block_date_from ?? null,
            'warehouses' => $this->warehouses_data ? $this->warehouses_data->map(function ($wh) {
                return [
                    'id' => $wh->id,
                    'code' => $wh->warehouse_code,
                    'name' => $wh->warehouse_name,
                    'location' => $wh->locationRelation?->name,
                    'selling_currency' => $wh->companyRelation?->selling_currency,
                    'purchase_currency' => $wh->companyRelation?->purchase_currency,
                ];
            }) : [],
            'device_no'   => $this->device_no ?? null,
            'token_no'   => $this->token_no ?? null,
            'sap_id'   => $this->sap_id ?? null,
            'is_take'   => $this->is_take ?? null,
           
        ];
    }
}
