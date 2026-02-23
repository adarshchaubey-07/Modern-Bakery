<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRODetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'header_id'    => $this->header_id,
            'customer_id'  => $this->customer_id,
            'chillerRequest' => $this->chillerRequest ? [
                'id' => $this->chillerRequest->id,
                'uuid' => $this->chillerRequest->uuid ?? null,
            ] : null,
            'crf_id'       => $this->crf_id,
            'warehouse'       => $this->warehouse_id,
            'warehouse_code'       => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'       => $this->warehouse->warehouse_name ?? null,
            'created_date' => $this->created_date,
            'created_user' => $this->created_user,
            'updated_user' => $this->updated_user,
        ];
    }
}
