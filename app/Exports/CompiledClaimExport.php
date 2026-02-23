<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompiledClaimExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            "id",
            "osa_code",
            "claim_period",
            "warehouse_code",
            "warehouse_name",
            "approved_qty_cse",
            "approved_claim_amount",
            "rejected_qty_cse",
            "rejected_amount",
            "area_sales_supervisor",
            "regional_sales_manager",
            "month_range",
            "promo_count",
            "promo_qty",
            "promo_amount",
            "reject_qty",
            "rejecte_amount",
            "agent_id",
            "agent_actiondate",
            "supervisor_id",
            "asm_actiondate",
            "manager_id",
            "manger_actiondate",
            "rejected_reason",
            "status",
            "verifier_id",
            "reject_comment",
            "asm_comment",
            "rm_comment",
            "agent_comment"
        ];
    }

    public function array(): array
    {
        return $this->data->map(function ($item) {
            return [
                $item->id,
                $item->osa_code,
                $item->claim_period,
                $item->warehouse->warehouse_code ?? null,
                $item->warehouse->warehouse_name ?? null,
                $item->approved_qty_cse,
                $item->approved_claim_amount,
                $item->rejected_qty_cse,
                $item->rejected_amount,
                $item->area_sales_supervisor,
                $item->regional_sales_manager,
                $item->month_range,
                $item->promo_count,
                $item->promo_qty,
                $item->promo_amount,
                $item->reject_qty,
                $item->rejecte_amount,
                $item->agent_id,
                $item->agent_actiondate,
                $item->supervisor_id,
                $item->asm_actiondate,
                $item->manager_id,
                $item->manger_actiondate,
                $item->rejected_reason,
                $item->status,
                $item->verifier_id,
                $item->reject_comment,
                $item->asm_comment,
                $item->rm_comment,
                $item->agent_comment
            ];
        })->toArray();
    }
}
