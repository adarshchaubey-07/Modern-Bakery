<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IROHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'uuid'       => $this->uuid,
            'osa_code'   => $this->osa_code,
            'name'   => $this->name,
            // 'crf_id'   => $this->crf_id,
            'status'     => $this->status,
            'created_user' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'username' => $this->createdBy->username ?? null,
            ] : null,
            'updated_user' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'username' => $this->createdBy->username ?? null,
            ] : null,

            'details' => IRODetailResource::collection($this->details),
        ];
    }
}
