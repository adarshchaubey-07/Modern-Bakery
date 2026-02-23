<?php

namespace App\Services\V1\Agent_Transaction\Mob;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\AgentDeliveryDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Helpers\DataAccessHelper;

class AgentDeliveryHeaderService
{
public static function createFromOrder(OrderHeader $order,string $deliveryCode): void
{
    if (
        AgentDeliveryHeaders::where('order_code', $order->order_code)->exists()
    ) {
        return;
    }
    $deliveryHeader = AgentDeliveryHeaders::create([
        'uuid'          => Str::uuid(),
        'delivery_code' => $deliveryCode,
        'order_code'    => $order->order_code,
        'customer_id'   => $order->customer_id,
        'route_id'      => $order->route_id,
        'salesman_id'   => $order->salesman_id,
        'currency'      => $order->currency,
        'gross_total'   => $order->gross_total,
        'vat'           => $order->vat,
        'discount'      => $order->discount,
        'net_amount'    => $order->net_amount,
        'total'         => $order->total,
        'comment'       => $order->comment,
        'status'        => 1,
        'latitude'      => $order->latitude ?? 0,
        'longitude'     => $order->longitude ?? 0,
    ]);

    foreach ($order->details as $detail) {
        AgentDeliveryDetails::create([
            'uuid'        => Str::uuid(),
            'header_id'   => $deliveryHeader->id,
            'item_id'     => $detail->item_id,
            'uom_id'      => $detail->uom_id,
            'item_price'  => $detail->item_price,
            'quantity'    => (int) $detail->quantity,
            'vat'         => $detail->vat,
            'discount'    => $detail->discount ?? 0,
            'gross_total' => $detail->gross_total,
            'net_total'   => $detail->net_total,
            'total'       => $detail->total,
        ]);
    }
}
private function generateDeliveryCode(?string $manualCode = null): string
{
    if (!empty($manualCode)) {
        return $manualCode;
    }
    $prefix = 'DL';
    $last = AgentDeliveryHeaders::where('delivery_code', 'LIKE', "{$prefix}%")
        ->orderByDesc('id')
        ->value('delivery_code');
    if ($last) {
        $number = (int) substr($last, strlen($prefix));
        $next = $number + 1;
    } else {
        $next = 1;
    }
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}
public function all($perPage = 50)
    {
        try {
            //$user = auth()->user();
            $query = AgentDeliveryHeaders::with([
                'details',
                'details.item.itemUoms',
                'warehouse:id,warehouse_name,warehouse_code',
                'country:id,country_name,country_code',
                'route:id,route_name,route_code',
            ])->latest();
            //$query = DataAccessHelper::filterAgentTransaction($query, $user);
            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch delivery headers: " . $e->getMessage());
        }
    }
}