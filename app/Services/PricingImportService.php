<?php

namespace App\Services;

use App\Models\PricingHeader;
use App\Models\PricingDetail;
use App\Models\Item;
use App\Models\Uom;
use App\Models\OutletChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PricingImportService
{
    protected array $items;
    protected array $uoms;
    protected array $channels;

    public function __construct()
    {
        $this->items    = Item::pluck('id', 'code')->toArray();
        $this->uoms     = Uom::pluck('id', 'name')->toArray();
        $this->channels = OutletChannel::pluck('id', 'outlet_channel_code')->toArray();
    }

    public function import(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {

            $headerIds   = [];
            $detailsData = [];

            foreach ($rows as $row) {

                if (
                    empty($row['Material']) ||
                    empty($row['DistChannel']) ||
                    empty($row['UOM']) ||
                    !isset($this->items[$row['Material']]) ||
                    !isset($this->channels[$row['DistChannel']]) ||
                    !isset($this->uoms[$row['UOM']])
                ) {
                    continue;
                }

                $channelId = $this->channels[$row['DistChannel']];

                if (!isset($headerIds[$channelId])) {
                    $header = PricingHeader::updateOrCreate(
                        [
                            'company_id'        => 1,
                            'outlet_channel_id' => $channelId,
                        ],
                        [
                            'status'       => 1,
                            'created_user' => Auth::id(),
                            'updated_user' => Auth::id(),
                        ]
                    );

                    if (!$header->uuid) {
                        $header->uuid = Str::uuid();
                        $header->save();
                    }

                    $headerIds[$channelId] = $header->id;
                }

                $detailsData[] = [
                    'uuid'         => Str::uuid(),
                    'header_id'    => $headerIds[$channelId],
                    'item_id'      => $this->items[$row['Material']],
                    'uom_id'       => $this->uoms[$row['UOM']],
                    'price'        => $row['Amount'],
                    'status'       => 1,
                    'created_user' => Auth::id(),
                ];
            }

            PricingDetail::upsert(
                $detailsData,
                ['header_id', 'item_id', 'uom_id'],
                ['price', 'status', 'updated_at']
            );
        });
    }
}
