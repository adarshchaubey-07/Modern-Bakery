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
            'type' => $this->type,
            'type_name' => $this->salesmanType?->salesman_type_name ?? null,
            'role_id' => $this->role_id,
            'role_name' => $this->role?->name ?? null,
            'name'     => $this->name ?? null,
            'email'    => $this->email ?? null,
            'contact_no'   => $this->contact_no ?? null,
            'route_id' => $this->route_id ?? null,
            'route_name' => $this->route?->route_name ?? null,
            'channel_id'   => $this->channel_id ?? null,
            'channel_name' => $this->channel?->outlet_channel ?? null,
            'block_date_to'   => $this->block_date_to ?? null,
            'block_date_from'   => $this->block_date_from ?? null,
            // 'attendance' => [
            // 'uuid'     => $this->attendance['uuid'] ?? null,
            // 'date'     => $this->attendance['date'] ?? null,
            // 'check_in' => $this->attendance['check_in'] ?? 0,
            //  ], 
            'device_no'   => $this->device_no ?? null,
            'token_no'   => $this->token_no ?? null,
        ];
    }
}
