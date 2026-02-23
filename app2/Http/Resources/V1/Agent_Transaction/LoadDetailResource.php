<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class LoadDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'header_id' => $this->header_id,
            // 'item_id' => $this->item_id,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name
            ] : null,
            'item_uom' => $this->itemUom ? [
                'id' => $this->itemUom->id,
                'name' => $this->itemUom->name,
                'uom_type' => $this->itemUom->uom_type
            ] : null,
            'uom' => $this->uom,
            // 'uom_name' => $this->itemUom->name ?? null,
            'qty' => $this->qty,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
