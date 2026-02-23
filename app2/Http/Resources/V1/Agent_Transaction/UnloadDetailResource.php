<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnloadDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
     public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name
            ] : null,
            'uom' => $this->uom,
            'uom_name' => $this->itemuom->name ?? null,
            'qty' => $this->qty,
            'status' => $this->status,
        ];
    }
}
