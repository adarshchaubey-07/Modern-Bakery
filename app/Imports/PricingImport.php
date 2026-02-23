<?php

namespace App\Imports;

use App\Models\PricingHeader;
use App\Models\PricingDetail;
use App\Models\Item;
use App\Models\Uom;
use App\Models\OutletChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class PricingImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithBatchInserts
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

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $headerIds   = [];
            $detailsData = [];

            foreach ($rows as $row) {

                if (
                    empty($row['material_code']) ||
                    empty($row['channel_id']) ||
                    empty($row['uom2']) ||
                    !isset($this->items[$row['material_code']]) ||
                    !isset($this->channels[$row['channel_id']]) ||
                    !isset($this->uoms[$row['uom2']])
                ) {
                    continue;
                }

                $channelId = $this->channels[$row['channel_id']];

                if (!isset($headerIds[$channelId])) {
                    $header = PricingHeader::updateOrCreate(
                        [
                            'company_id'        => 1,
                            'outlet_channel_id' => $channelId,
                        ],
                        [
                            'status'       => 1,
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
                    'item_id'      => $this->items[$row['material_code']],
                    'uom_id'       => $this->uoms[$row['uom2']],
                    'price'        => $row['amount'],
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

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
