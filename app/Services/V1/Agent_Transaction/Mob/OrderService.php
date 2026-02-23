<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\AgentDeliveryDetails;
use App\Services\V1\Agent_Transaction\Mob\AgentDeliveryHeaderService;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
// use App\Helpers\DataAccessHelper;


class OrderService
{
public function __construct(
private AgentDeliveryHeaderService $deliveryService
    ) {}
// public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
// {
//     // $user = auth()->user();

//     $query = OrderHeader::with([
//         'warehouse',
//         'route',
//         'warehouse.getCompany',
//         'country',
//         'customer',
//         'salesman',
//         'createdBy',
//         'updatedBy',
//         'details.item.itemUoms',
//         'details.uom',
//         'details.discount',
//         'details.promotion',
//         'details.parent',
//         'details.children'
//     ]);

//     // $query = DataAccessHelper::filterAgentTransaction($query, $user);

//     if (isset($filters['order_flag'])) {
//         $query->where('order_flag', $filters['order_flag']);
//     }
//     if (!empty($filters['no_delivery']) && $filters['no_delivery'] == true) {
//         $query->where('order_flag', 1);
//     }
//     if (!empty($filters['search'])) {
//         $search = $filters['search'];
//         $query->where(function ($q) use ($search) {
//             $q->where('order_code', 'LIKE', '%' . $search . '%')
//                 ->orWhere('comment', 'LIKE', '%' . $search . '%')
//                 ->orWhere('status', 'LIKE', '%' . $search . '%')
//                 ->orWhere('delivery_date', 'LIKE', '%' . $search . '%')
//                 ->orWhereHas('warehouse', function ($q2) use ($search) {
//                     $q2->where('warehouse_code', 'LIKE', '%' . $search . '%')
//                         ->orWhere('warehouse_name', 'LIKE', '%' . $search . '%')
//                         ->orWhere('owner_email', 'LIKE', '%' . $search . '%')
//                         ->orWhere('owner_number', 'LIKE', '%' . $search . '%')
//                         ->orWhere('address', 'LIKE', '%' . $search . '%');
//                 })
//                 ->orWhereHas('customer', function ($q2) use ($search) {
//                     $q2->where('osa_code', 'LIKE', '%' . $search . '%')
//                         ->orWhere('name', 'LIKE', '%' . $search . '%')
//                         ->orWhere('email', 'LIKE', '%' . $search . '%')
//                         ->orWhere('street', 'LIKE', '%' . $search . '%')
//                         ->orWhere('town', 'LIKE', '%' . $search . '%')
//                         ->orWhere('contact_no', 'LIKE', '%' . $search . '%');
//                 })
//                 ->orWhereHas('route', function ($q2) use ($search) {
//                     $q2->where('route_code', 'LIKE', '%' . $search . '%')
//                         ->orWhere('route_name', 'LIKE', '%' . $search . '%');
//                 })
//                 ->orWhereHas('salesman', function ($q2) use ($search) {
//                     $q2->where('osa_code', 'LIKE', '%' . $search . '%')
//                         ->orWhere('name', 'LIKE', '%' . $search . '%');
//                 })
//                 ->orWhereHas('details', function ($q2) use ($search) {
//                     $q2->where('quantity', 'LIKE', '%' . $search . '%')
//                         ->orWhere('item_price', 'LIKE', '%' . $search . '%')
//                         ->orWhere('vat', 'LIKE', '%' . $search . '%')
//                         ->orWhere('discount', 'LIKE', '%' . $search . '%')
//                         ->orWhere('gross_total', 'LIKE', '%' . $search . '%')
//                         ->orWhere('net_total', 'LIKE', '%' . $search . '%')
//                         ->orWhere('total', 'LIKE', '%' . $search . '%')
//                         ->orWhereHas('item', function ($q3) use ($search) {
//                             $q3->where('code', 'LIKE', '%' . $search . '%')
//                                 ->orWhere('name', 'LIKE', '%' . $search . '%')
//                                 ->orWhereHas('itemUoms', function ($q4) use ($search) {
//                                     $q4->where('name', 'LIKE', '%' . $search . '%')
//                                         ->orWhere('price', 'LIKE', '%' . $search . '%')
//                                         ->orWhere('upc', 'LIKE', '%' . $search . '%')
//                                         ->orWhere('uom_type', 'LIKE', '%' . $search . '%');
//                                 });
//                         })
//                         ->orWhereHas('uom', function ($q3) use ($search) {
//                             $q3->where('name', 'LIKE', '%' . $search . '%');
//                         });
//                 });
//         });
//     }

//     if (!empty($filters['warehouse_id'])) {
//         $query->where('warehouse_id', $filters['warehouse_id']);
//     }

//     if (!empty($filters['order_code'])) {
//         $query->where('order_code', 'LIKE', '%' . $filters['order_code'] . '%');
//     }

//     if (!empty($filters['customer_id'])) {
//         $query->where('customer_id', $filters['customer_id']);
//     }

//     if (!empty($filters['delivery_date'])) {
//         $query->where('delivery_date', $filters['delivery_date']);
//     }

//     if (!empty($filters['salesman_id'])) {
//         $query->where('salesman_id', $filters['salesman_id']);
//     }

//     if (!empty($filters['comment'])) {
//         $query->where('comment', 'LIKE', '%' . $filters['comment'] . '%');
//     }

//     if (!empty($filters['status'])) {
//         $query->where('status', $filters['status']);
//     }

//     if (!empty($filters['from_date'])) {
//         $query->whereDate('created_at', '>=', $filters['from_date']);
//     }

//     if (!empty($filters['to_date'])) {
//         $query->whereDate('created_at', '<=', $filters['to_date']);
//     }

//     if (!empty($filters['country_id'])) {
//         $query->where('country_id', $filters['country_id']);
//     }

//     $query->when(!empty($filters['item_id']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('item_id', $filters['item_id']);
//         });
//     });

//     $query->when(!empty($filters['item_name']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->whereHas('item', function ($q3) use ($filters) {
//                 $q3->where('name', 'LIKE', '%' . $filters['item_name'] . '%');
//             });
//         });
//     });

//     $query->when(!empty($filters['uom_id']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('uom_id', $filters['uom_id']);
//         });
//     });

//     $query->when(!empty($filters['item_price']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('item_price', $filters['item_price']);
//         });
//     });

//     $query->when(!empty($filters['quantity']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('quantity', $filters['quantity']);
//         });
//     });

//     $query->when(!empty($filters['net_total']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('net_total', $filters['net_total']);
//         });
//     });

//     $query->when(!empty($filters['gross_total']), function ($q) use ($filters) {
//         $q->whereHas('details', function ($q2) use ($filters) {
//             $q2->where('gross_total', $filters['gross_total']);
//         });
//     });

//     $sortBy = $filters['sort_by'] ?? 'created_at';
//     $sortOrder = $filters['sort_order'] ?? 'desc';
//     $query->orderBy($sortBy, $sortOrder);

//     if ($dropdown) {
//         return $query->get()->map(function ($order) {
//             return [
//                 'id' => $order->id,
//                 'label' => $order->order_code,
//                 'value' => $order->id,
//             ];
//         });
//     }

//     return $query->paginate($perPage);
// }

// public function getByUuid(string $uuid)
// {
//     try {
//         $current = OrderHeader::with([
//             'warehouse',
//             'warehouse.getCompany',
//             'route',
//             'customer',
//             'salesman',
//             'createdBy',
//             'updatedBy',
//             'details' => function ($q) {
//                 $q->with([
//                     'item',
//                     'uom',
//                     'discount',
//                     'promotion',
//                     'children.item',
//                     'children.uom',
//                     'children.discount',
//                     'children.promotion',
//                 ]);
//             },
//         ])->where('uuid', $uuid)->first();
//         if (!$current) {
//             return null;
//         }
//         $previousUuid = OrderHeader::where('id', '<', $current->id)
//             ->orderBy('id', 'desc')
//             ->value('uuid');
//         $nextUuid = OrderHeader::where('id', '>', $current->id)
//             ->orderBy('id', 'asc')
//             ->value('uuid');
//         $current->previous_uuid = $previousUuid;
//         $current->next_uuid = $nextUuid;
//         return $current;
//     } catch (\Exception $e) {
//         \Log::error('OrderService::getByUuid Error: ' . $e->getMessage());
//         return null;
//     }
// }
public function create(array $data): ?OrderHeader
{
    DB::beginTransaction();
    try {
        try {
            $header = OrderHeader::create([
                'order_code'    => $data['order_code'] ?? null,
                'delivery_date' => $data['delivery_date'],
                'delivery_time' => $data['delivery_time'] ?? null,
                'customer_id'   => $data['customer_id'],
                'comment'       => $data['comment'] ?? null,
                'status'        => $data['status'] ?? 1,
                'currency'      => $data['currency'] ?? null,
                'route_id'      => $data['route_id'],
                'salesman_id'   => $data['salesman_id'] ?? null,
                'gross_total'   => $data['gross_total'] ?? 0,
                'vat'           => $data['vat'] ?? 0,
                'net_amount'    => $data['net_amount'] ?? 0,
                'total'         => $data['total'] ?? 0,
                'discount'      => $data['discount'] ?? 0,
                'latitude'      => $data['latitude'] ?? 0,
                'longitude'     => $data['longitude'] ?? 0,
                'sap_status'    => 0,
                'customer_lpo'  => $data['customer_lpo'] ?? null,
                'division'      => $data['division'] ?? 0,
                'doc_type'      => $data['doc_type'] ?? null,
            ]);
        } catch (\Throwable $e) {
            throw new \Exception('Order creation failed while creating order header');
        }
        try {
            if (empty($data['details'])) {
                throw new \Exception('Order details are missing');
            }
            foreach ($data['details'] as $detail) {
                OrderDetail::create([
                    'header_id'      => $header->id,
                    'item_id'        => $detail['item_id'],
                    'uom_id'         => $detail['uom_id'],
                    'status'         => $detail['status'] ?? 1,
                    'gross_total'    => $detail['gross_total'] ?? 0,
                    'net_total'      => $detail['net_total'] ?? 0,
                    'total'          => $detail['total'] ?? 0,
                    'item_price'     => $detail['item_price'] ?? 0,
                    'quantity'       => $detail['quantity'] ?? 0,
                    'vat'            => $detail['vat'] ?? 0,
                    'promotion_id'   => $detail['promotion_id'] ?? null,
                    'parent_id'      => $detail['parent_id'] ?? null,
                    'discount'       => $detail['discount'] ?? 0,
                    'is_promotional' => $detail['is_promotional'] ?? false,
                ]);
            }
        } catch (\Throwable $e) {
            throw new \Exception(
                'Order creation failed while creating order details'
            );
        }
        try {
            $header->load(['details', 'details.item', 'details.uoms', 'route']);
            $sapPayload = $this->buildSapPayload($header);

            $header->update([
                'sap_payload' => json_encode($sapPayload, JSON_UNESCAPED_UNICODE),
                'sap_status'  => 0
            ]);
        } catch (\Throwable $e) {
            throw new \Exception('Order creation failed while building SAP payload');
        }
        try {
            $deliveryCode = $this->generateDeliveryCode();
            $this->deliveryService->createFromOrder($header, $deliveryCode);
        } catch (\Throwable $e) {
            throw new \Exception(
                'Order created but delivery creation failed: ' . $e->getMessage()
            );
        }
        DB::commit();
        return $header;
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('OrderService::create failed', [
            'message'  => $e->getMessage(),
            'order_id'=> $header->id ?? null
        ]);
        throw $e;
    }
}
private function buildSapPayload(OrderHeader $order): array
{
    $order->loadMissing(['details', 'details.item','details.uoms','route','customer','salesman']);
    $route = optional($order->route)->route_code ?? 'DEFAULT';
    $uom = optional($order->Uom)->name ?? 'EA';
    $customer = optional($order->customer)->osa_code ?? 'UNKNOWN';
    $salesman = optional($order->salesman)->osa_code ?? 'UNKNOWN';
    $soItems = $order->details->map(function ($item, $index) use ($route) {
        $itemNumber = str_pad(($index + 1) * 10, 4, '0', STR_PAD_LEFT);
        return [
            "Item"            => $itemNumber,
            "Storagelocation" => "",
            "UoM"             => optional($item->uoms)->name ?? 'EA',
            "Description"     => optional($item->item)->name ?? 'EA',
            "ItemValue"       => number_format($item->item_price ?? 0, 2, '.', ''),
            "Value"           => number_format($item->net_total ?? 0, 2, '.', ''),
            "Quantity"        => (string) ($item->quantity ?? 0),
            "Material"        => optional($item->item)->code ?? 'UNKNOWN',
            "Route"           => $route,
        ];
    })->toArray();
    return [
            "Function"     => "ORDERREQ",
            "OrderValue"   => $order->total ?? 0,
            "SalesOrg"     => "1000",
            "Currency"     => $order->currency ?? 'AED',
            "StartDate"    => $order->delivery_date ?? '',
            "StartTime"    => $order->delivery_time ?? '',
            "DocumentType" => $order->doc_type ?? 'ZOR', // default doc type
            "RefCust"      => $salesman,
            "Division"     => (string) ($order->division ?? 0),
            "CustomerId"   => $customer,
            "OrderId"      => "",
            "PurchaseNum"  => $order->order_code ?? '',
            "CustomerLPO"  => $order->customer_lpo ?? '',
            "SOItems"      => $soItems,
        
    ];
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
}