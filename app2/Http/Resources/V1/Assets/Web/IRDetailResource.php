<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'fridge_id' => $this->fridge_id,
            'agreement_id' => $this->agreement_id,
            'crf_id' => $this->crf_id
        ];
    }
}
