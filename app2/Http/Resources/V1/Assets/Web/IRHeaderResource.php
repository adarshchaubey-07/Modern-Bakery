<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'iro_id' => $this->iro_id,
            'osa_code' => $this->osa_code,
            'salesman_id' => $this->salesman_id,
            'schedule_date' => $this->schedule_date,
            'status' => $this->status,
            'details' => IRDetailResource::collection($this->details)
        ];
    }
}
