<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'          => $this->uuid,
            'route_code'  => $this->route_code,
            'route_name'  => $this->route_name,
            'vehicle' => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'code' => $this->vehicle->vehicle_code,
                'capacity' => $this->vehicle->capacity
            ] : null,
            'region' => $this->region ? [
                'id' => $this->region->id,
                'code' => $this->region->region_code,
                'name' => $this->region->region_name
            ] : null,
            'getrouteType' => $this->getrouteType ? [
                'id' => $this->getrouteType->id,
                'code' => $this->getrouteType->route_type_code,
                'name' => $this->getrouteType->route_type_name
            ] : null,
            'customer_count' => $this->customers_count ?? 0,
            'status'      => $this->status,
            'createdBy'   => $this->createdBy,
            'updatedBy'   => $this->updatedBy,
        ];
    }
}
