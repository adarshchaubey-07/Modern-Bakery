<?php

namespace App\Http\Resources\V1\Claim_Management\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CompiledClaimResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "uuid" => $this->uuid,
            "osa_code" => $this->osa_code,
            "claim_period" => $this->claim_period,
            // "warehouse_id" => $this->warehouse_id,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,

            "approved_qty_cse" => $this->approved_qty_cse,
            "approved_claim_amount" => $this->approved_claim_amount,
            "rejected_qty_cse" => $this->rejected_qty_cse,
            "rejected_amount" => $this->rejected_amount,

            "area_sales_supervisor" => $this->area_sales_supervisor,
            "regional_sales_manager" => $this->regional_sales_manager,

            "month_range" => $this->month_range,
            "promo_count" => $this->promo_count,
            "promo_qty" => $this->promo_qty,
            "promo_amount" => $this->promo_amount,
            "reject_qty" => $this->reject_qty,
            "rejecte_amount" => $this->rejecte_amount,

            "agent_id" => $this->agent_id,
            "agent_actiondate" => $this->agent_actiondate,
            "supervisor_id" => $this->supervisor_id,
            "asm_actiondate" => $this->asm_actiondate,
            "manager_id" => $this->manager_id,
            "manger_actiondate" => $this->manger_actiondate,
            "rejected_reason" => $this->rejected_reason,

            "status" => $this->status,
            "verifier_id" => $this->verifier_id,
            "reject_comment" => $this->reject_comment,

            "asm_comment" => $this->asm_comment,
            "rm_comment" => $this->rm_comment,
            "agent_comment" => $this->agent_comment,

            "start_date" => $this->start_date,
            "end_date" => $this->end_date,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
