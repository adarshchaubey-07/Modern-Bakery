<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_code' => $this->order_code,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,
            // 'warehouse_email' => $this->warehouse->owner_email ?? null,
            'warehouse_number' => $this->warehouse->owner_number ?? null,
            'warehouse_address' => $this->warehouse->address ?? null,
            'warehouse_street' => $this->warehouse->street ?? null,
            'warehouse_town' => $this->warehouse->town_village ?? null,
            'customer_id' => $this->customer_id,
            'customer_code' => $this->customer->osa_code ?? null,
            'customer_name' => $this->customer->name ?? null,
            // 'customer_email' => $this->customer->email ?? null,
            'customer_street' => $this->customer->street ?? null,
            'customer_town' => $this->customer->town ?? null,
            'customer_contact' => $this->customer->contact_no ?? null,
            'route_id' => $this->route_id,
            'route_code' => $this->route->route_code ?? null,
            'route_name' => $this->route->route_name ?? null,
            'salesman_id' => $this->route_id,
            'salesman_code' => $this->route->osa_code ?? null,
            'salesman_name' => $this->route->name ?? null,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'details' => OrderDetailResource::collection($this->whenLoaded('details')),
            'previous_uuid' => $this->previous_uuid ?? null,
            'next_uuid'     => $this->next_uuid ?? null,
            'currency' => $this->currency,
            'order_flag'=>$this->order_flag,
            'latitude'=>$this->latitude,
            'longitude'=>$this->longitude,
            // 'country_id' => $this->country_id,
            // 'country_code'  => $this->country->country_code ?? null,
            // 'country_name'  => $this->country->country_name ?? null,
            // 'route_id' => $this->route_id,
            // 'route_code'    => $this->route->route_code ?? null,
            // 'route_name'    => $this->route->route_name ?? null,
            // 'salesman_id' => $this->salesman_id,
            // 'salesman_code' => $this->salesman->osa_code ?? null,
            // 'salesman_name' => $this->salesman->name ?? null,
            // 'gross_total' => (float) $this->gross_total,
            // 'vat' => (float) $this->vat,
            // 'net_amount' => (float) $this->net_amount,
            // 'total' => (float) $this->total,
            // 'discount' => (float) $this->discount,

        ];
    }
}
