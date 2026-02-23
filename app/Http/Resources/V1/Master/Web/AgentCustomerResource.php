<?php
namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentCustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'name' => $this->name,
            'customer_type' => $this->customer_type,
            'tin_no'=>$this->tin_no,
            'route' => $this->route ? [
                'id' => $this->route->id,
                'route_code' => $this->route->route_code ?? '',
                'route_name' => $this->route->route_name ?? '',
            ] : null,
            'salesman' => $this->salesman ? [
                'id'             => $this->salesman->id,
                'salesman_code'  => $this->salesman->osa_code ?? '',
                'salesman_name'  => $this->salesman->name ?? '',
            ] : null,
            'region' => $this->region ? [
                'id'          => $this->region->id ?? '',
                'region_code' => $this->region->region_code ?? '',
                'region_name' => $this->region->region_name ?? '',
            ] : null,
            'outlet_channel' => $this->outlet_channel ? [
                'id' => $this->outlet_channel->id,
                'outlet_channel_code' => $this->outlet_channel->outlet_channel_code,
                'outlet_channel' => $this->outlet_channel->outlet_channel,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'customer_category_code' => $this->category->customer_category_code,
                'customer_category_name' => $this->category->customer_category_name,
            ] : null,
            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'customer_sub_category_name'=>$this->subcategory->customer_sub_category_name, 
                'customer_sub_category_code' => $this->subcategory->customer_sub_category_code,
            ] : null,
            'city'  => $this->city,
            'landmark' => $this->landmark,
            'district' => $this->district,
            'street' => $this->street,
            'town' => $this->town,
            'whatsapp_no' => $this->whatsapp_no,
            'contact_no'=>$this->contact_no,
            'contact_no2' => $this->contact_no2,
            'payment_type' => $this->payment_type,
            'creditday' => $this->creditday,
            'credit_limit' => $this->credit_limit,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'account_group' => $this->account_group,
            'account_group' => $this->accountgrp ? [
                'id' => $this->accountgrp->id,
                'account_group_code' => $this->accountgrp->code,
                'account_group_name' => $this->accountgrp->name,
            ] : null,
            'is_driver' => $this->is_driver,
            'cust_group' => $this->cust_group,

        ];
    }
}
