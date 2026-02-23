<?php

namespace App\Http\Resources\V1\Claim_Management\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class PetitClaimResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "uuid" => $this->uuid,
            "osa_code" => $this->osa_code,
            "claim_type" => $this->claim_type,
            // "warehouse_id" => $this->warehouse_id,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,
            "petit_name" => $this->petit_name,
            "fuel_amount" => $this->fuel_amount,
            "rent_amount" => $this->rent_amount,
            "agent_amount" => $this->agent_amount,
            "month_range" => $this->month_range,
            "year" => $this->year,
            "claim_file" => $this->claim_file,
            "status" => $this->status,
            // "created_at" => $this->created_at,
        ];
    }
}
