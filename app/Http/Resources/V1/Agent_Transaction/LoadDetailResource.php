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
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name
            ] : null,
            'uom' => $this->uom,
            'uom_name' => $this->Uom->name ?? null,
            'item_uom' => $this->itemUOMS ? [
                'id' => $this->itemUOMS->id,
                'name' => $this->itemUOMS->name,
                'item_id' => $this->itemUOMS->item_id,
                'uom_type' => $this->itemUOMS->uom_type,
                'upc'      => $this->itemUOMS->upc,
                'uom_id'   => $this->itemUOMS->uom_id,
            ] : null,
            'qty' => $this->qty,
            'batch_no' => $this->batch_no,
            'batch_expiry_date' => $this->batch_expiry_date,
            'net_price' => $this->net_price,
            'status' => $this->status,
            'msp'    => $this->msp,
            'displayunit' => $this->displayunit,
            'displayunit_uom_name' => $this->Uom->name
        ];
    }
} 
