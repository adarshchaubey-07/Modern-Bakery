<?php

namespace App\Services;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\LoadDetail;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LoadImportService
{
    public function import(array $data): void
    {
        DB::transaction(function () use ($data) {

            $items   = Item::pluck('id', 'code');
            $uoms    = Uom::pluck('id', 'name');
            $userId  = Auth::id() ?? 1;

            foreach ($data as $row) {

                if (empty($row['DeliveryNo'])) {
                    continue;
                }
                $header = LoadHeader::firstOrCreate(
                    ['delivery_no' => trim($row['DeliveryNo'])],
                    [
                        'uuid'         => (string) Str::uuid(),
                        'status'       => 1,
                        'created_user' => $userId,
                        'updated_user' => $userId,
                    ]
                );

                if (empty($row['Items']) || !is_array($row['Items'])) {
                    continue;
                }

                foreach ($row['Items'] as $item) {

                    $materialCode = trim($item['MaterialNo'] ?? '');
                    $itemId       = $items[$materialCode] ?? null;

                    if (!$itemId) {
                        continue;
                    }

                    $uomName      = trim($item['Uom'] ?? '');
                    $displayName  = trim($item['DisplayUnit'] ?? '');

                    $uomId        = $uoms[$uomName] ?? null;
                    $displayId    = $uoms[$displayName] ?? null;

                    $expiryDate = null;
                    if (!empty($item['Batchexp'])) {
                        try {
                            $expiryDate = Carbon::createFromFormat(
                                'd.m.Y',
                                $item['Batchexp']
                            )->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $expiryDate = null;
                        }
                    }

                    $detail = LoadDetail::updateOrCreate(
                        [
                            'header_id' => $header->id,
                            'item_id'   => $itemId,
                            'batch_no'  => trim($item['Batch'] ?? ''),
                        ],
                        [
                            'qty'               => (float) ($item['ActQtyDel'] ?? 0),
                            'uom'               => $uomId,
                            'status'            => 1,
                            'batch_expiry_date' => $expiryDate,
                            'price'             => (float) ($item['UnitPrice'] ?? 0),
                            'net_price'         => (float) ($item['NetPrice'] ?? 0),
                            'msp'               => (float) ($item['MSP'] ?? 0),
                            'displayunit'       => $displayId,
                            'updated_user'      => $userId,
                        ]
                    );

                    if ($detail->wasRecentlyCreated) {
                        $detail->created_user = $userId;
                        $detail->save();
                    }
                }
            }
        });
    }
}
