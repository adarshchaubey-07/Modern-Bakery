<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\PromotionHeader;
use App\Models\PromotionDetail;
use App\Models\PromotionOfferItem;
use App\Models\PromotionalSlab;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PromotionHeaderService
{
    /**
     * CREATE
     */
    // public function create(array $data): PromotionHeader
    // {
    //     DB::beginTransaction();

    //     try {
    //         $osaCode = !empty($data['osa_code'])
    //             ? $data['osa_code']
    //             : $this->generateOsaCode();

    //         $offer      = $data['offer_items'][0] ?? [];
    //         $percentage = $data['percentage_discounts'][0] ?? [];

    //         $promotion = PromotionHeader::create([
    //             'uuid'               => Str::uuid(),
    //             'osa_code'           => $osaCode,
    //             'promotion_name'     => $data['promotion_name'],
    //             'promotion_type'     => $data['promotion_type'] ?? null,
    //             'bundle_combination' => $data['bundle_combination'] ?? null,
    //             'from_date'          => $data['from_date'],
    //             'to_date'            => $data['to_date'],
    //             'status'             => $data['status'],

    //             'offer_item_id' => $offer['item_id'],
    //             'offer_uom'     => $offer['uom'] ?? null,

    //             // ARRAY → CSV
    //             'sales_team_type' => isset($data['sales_team_type'])
    //                 ? implode(',', $data['sales_team_type'])
    //                 : null,

    //             'project_list' => !empty($data['project_list'])
    //                 ? implode(',', $data['project_list'])
    //                 : null,

    //             'uom' => $data['uom'] ?? null,

    //             'items'         => implode(',', $data['items'] ?? []),
    //             'item_category' => implode(',', $data['item_category'] ?? []),
    //             'location'      => implode(',', $data['location'] ?? []),
    //             'customer'      => implode(',', $data['customer'] ?? []),

    //             'key_location' => isset($data['key']['Location'])
    //                 ? implode(',', $data['key']['Location'])
    //                 : null,

    //             'key_customer' => isset($data['key']['Customer'])
    //                 ? implode(',', $data['key']['Customer'])
    //                 : null,

    //             // PERCENTAGE
    //             'percentage_item_id'       => $percentage['percentage_item_id'] ?? null,
    //             'percentage_item_category' => $percentage['percentage_item_category'] ?? null,
    //             'percentage'               => $percentage['percentage'] ?? null,

    //             'created_user' => auth()->id(),
    //         ]);

    //         foreach ($data['promotion_details'] as $index => $detail) {
    //             PromotionDetail::create([
    //                 'header_id'    => $promotion->id,
    //                 'from_qty'     => (int) $detail['from_qty'],
    //                 'to_qty'       => (int) $detail['to_qty'],
    //                 'free_qty'     => (int) $detail['free_qty'],
    //                 'created_user' => auth()->id(),
    //             ]);
    //         }

    //         DB::commit();

    //         return $promotion->load('promotionDetails');
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         \Log::error('[PROMOTION CREATE FAILED]', [
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);

    //         throw new \Exception(
    //             'Failed to create promotion. Please try again later.',
    //             500,
    //             $e
    //         );
    //     }
    // }

    public function create(array $data): PromotionHeader
    {
        DB::beginTransaction();

        try {

            $osaCode = $data['osa_code'] ?? $this->generateOsaCode();

            /*
        |--------------------------------------------------------------------------
        | 1. PROMOTION HEADER
        |--------------------------------------------------------------------------
        */
            $promotion = PromotionHeader::create([
                'uuid'               => Str::uuid(),
                'osa_code'           => $osaCode,
                'promotion_name'     => $data['promotion_name'],
                'promotion_type'     => $data['promotion_type'],
                'bundle_combination' => $data['bundle_combination'],
                'from_date'          => $data['from_date'],
                'to_date'            => $data['to_date'],
                'status'             => $data['status'],

                'sales_team_type' => !empty($data['sales_team_type'])
                    ? implode(',', $data['sales_team_type'])
                    : null,

                'project_list' => !empty($data['project_list'])
                    ? implode(',', $data['project_list'])
                    : null,

                'uom' => $data['uom'] ?? null,

                'items'         => implode(',', $data['items'] ?? []),
                'item_category' => implode(',', $data['item_category'] ?? []),
                'location'      => implode(',', $data['location'] ?? []),
                'customer'      => implode(',', $data['customer'] ?? []),

                'key_location' => $data['key']['Location'][0] ?? null,
                'key_customer' => $data['key']['Customer'][0] ?? null,
                'key_item' => $data['key']['Item'][0] ?? null,

                'created_user' => auth()->id(),
            ]);

            $details     = $data['promotion_details'] ?? [];
            $offers      = $data['offer_items'] ?? [];
            $percentages = $data['percentage_discounts'] ?? [];

            /*
        |--------------------------------------------------------------------------
        | 2. PROMOTION DETAILS (QTY SLABS)
        |--------------------------------------------------------------------------
        */
            foreach ($details as $detail) {

                $promotionDetail = PromotionDetail::create([
                    'header_id'   => $promotion->id,
                    'from_qty'    => (int) $detail['from_qty'],
                    'to_qty'      => (int) $detail['to_qty'],
                    'free_qty'    => (int) $detail['free_qty'],
                    'created_user' => auth()->id(),
                ]);

                /*
            |--------------------------------------------------------------------------
            | 3. PROMOTIONAL ITEMS DETAIL
            |--------------------------------------------------------------------------
            */
                foreach ($offers as $offer) {

                    if (!empty($percentages)) {
                        foreach ($percentages as $percentage) {

                            PromotionOfferItem::create([
                                'promotion_header_id' => $promotion->id,
                                'offer_item_id'       => $offer['item_id'],
                                'uom'                 => $offer['uom'],

                                'percentage_item_id'     => $percentage['percentage_item_id'],
                                'percentage_category_id' => $percentage['percentage_item_category'],
                                'percentage_uom'         => $offer['uom'],

                                'created_user' => auth()->id(),
                            ]);
                        }
                    } else {

                        PromotionOfferItem::create([
                            'promotion_header_id' => $promotion->id,
                            'offer_item_id'       => $offer['item_id'],
                            'uom'                 => $offer['uom'],
                            'created_user'        => auth()->id(),
                        ]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 4. PROMOTIONAL SLABS (ONLY FOR SLAB TYPE)
        |--------------------------------------------------------------------------
        */
            if ($data['bundle_combination'] === 'slab') {

                foreach ($percentages as $percentage) {

                    PromotionalSlab::create([
                        'promotion_header_id' => $promotion->id,
                        'category'            => $percentage['percentage_item_category'],
                        'item_id'             => $percentage['percentage_item_id'],
                        'percentage'       => $percentage['percentage'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $promotion->load([
                'promotionDetails',
                'offerItems',
                'promotionalSlabs'
            ]);
        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();

            \Log::error('[PROMOTION CREATE FAILED]', [
                'error' => $e->getMessage(),
                'payload' => $data,
            ]);

            throw new \Exception(
                'Failed to create promotion. Please try again later.',
                500,
                $e
            );
        }
    }




    /**
     * UPDATE
     */
    // public function update(string $uuid, array $data): PromotionHeader
    // {
    //     DB::beginTransaction();

    //     try {
    //         $promotion = PromotionHeader::where('uuid', $uuid)->firstOrFail();

    //         $offer      = $data['offer_items'][0] ?? [];
    //         // $percentage = $data['percentage_discounts'][0] ?? [];

    //         $promotion->update([
    //             'promotion_name'     => $data['promotion_name'] ?? $promotion->promotion_name,
    //             'promotion_type'     => $data['promotion_type'] ?? $promotion->promotion_type,
    //             'bundle_combination' => $data['bundle_combination'] ?? $promotion->bundle_combination,
    //             'from_date'          => $data['from_date'] ?? $promotion->from_date,
    //             'to_date'            => $data['to_date'] ?? $promotion->to_date,
    //             'status'             => $data['status'] ?? $promotion->status,

    //             // OFFER
    //             'offer_item_id' => $offer['item_id'] ?? $promotion->offer_item_id,
    //             'offer_uom'     => $offer['uom'] ?? $promotion->offer_uom,

    //             // ARRAY → CSV
    //             'sales_team_type' => isset($data['sales_team_type'])
    //                 ? implode(',', $data['sales_team_type'])
    //                 : $promotion->sales_team_type,

    //             'project_list' => isset($data['project_list'])
    //                 ? implode(',', $data['project_list'])
    //                 : $promotion->project_list,

    //             'uom' => $data['uom'] ?? $promotion->uom,

    //             'items'         => isset($data['items']) ? implode(',', $data['items']) : $promotion->items,
    //             'item_category' => isset($data['item_category']) ? implode(',', $data['item_category']) : $promotion->item_category,
    //             'location'      => isset($data['location']) ? implode(',', $data['location']) : $promotion->location,
    //             'customer'      => isset($data['customer']) ? implode(',', $data['customer']) : $promotion->customer,

    //             'key_location' => isset($data['key']['Location'])
    //                 ? implode(',', $data['key']['Location'])
    //                 : $promotion->key_location,

    //             'key_customer' => isset($data['key']['Customer'])
    //                 ? implode(',', $data['key']['Customer'])
    //                 : $promotion->key_customer,

    //             // PERCENTAGE
    //             'percentage_item_id'       => $percentage['percentage_item_id'] ?? $promotion->percentage_item_id,
    //             'percentage_item_category' => $percentage['percentage_item_category'] ?? $promotion->percentage_item_category,
    //             'percentage'               => $percentage['percentage'] ?? $promotion->percentage,

    //             'updated_user' => auth()->id(),
    //         ]);

    //         // DETAILS — replace only if sent
    //         if (array_key_exists('promotion_details', $data)) {
    //             $promotion->promotionDetails()->delete();

    //             foreach ($data['promotion_details'] ?? [] as $detail) {
    //                 PromotionDetail::create([
    //                     'header_id'    => $promotion->id,
    //                     'from_qty'     => (int) $detail['from_qty'],
    //                     'to_qty'       => (int) $detail['to_qty'],
    //                     'free_qty'     => (int) $detail['free_qty'],
    //                     'created_user' => auth()->id(),
    //                 ]);
    //             }
    //         }


    //         DB::commit();

    //         return $promotion->load('promotionDetails');
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         \Log::error('[PROMOTION UPDATE FAILED]', [
    //             'uuid'  => $uuid,
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);

    //         throw new \Exception(
    //             'Failed to update promotion. Please try again later.',
    //             500,
    //             $e
    //         );
    //     }
    // }

    public function update(string $uuid, array $data): PromotionHeader
    {
        DB::beginTransaction();

        try {

            $promotion = PromotionHeader::where('uuid', $uuid)->firstOrFail();

            /*
        |--------------------------------------------------------------------------
        | 1. UPDATE PROMOTION HEADER
        |--------------------------------------------------------------------------
        */
            $promotion->update([
                'promotion_name'     => $data['promotion_name']     ?? $promotion->promotion_name,
                'promotion_type'     => $data['promotion_type']     ?? $promotion->promotion_type,
                'bundle_combination' => $data['bundle_combination'] ?? $promotion->bundle_combination,
                'from_date'          => $data['from_date']          ?? $promotion->from_date,
                'to_date'            => $data['to_date']            ?? $promotion->to_date,
                'status'             => $data['status']             ?? $promotion->status,

                'sales_team_type' => array_key_exists('sales_team_type', $data)
                    ? implode(',', $data['sales_team_type'])
                    : $promotion->sales_team_type,

                'project_list' => array_key_exists('project_list', $data)
                    ? implode(',', $data['project_list'])
                    : $promotion->project_list,

                'uom' => $data['uom'] ?? $promotion->uom,

                'items' => array_key_exists('items', $data)
                    ? implode(',', $data['items'])
                    : $promotion->items,

                'item_category' => array_key_exists('item_category', $data)
                    ? implode(',', $data['item_category'])
                    : $promotion->item_category,

                'location' => array_key_exists('location', $data)
                    ? implode(',', $data['location'])
                    : $promotion->location,

                'customer' => array_key_exists('customer', $data)
                    ? implode(',', $data['customer'])
                    : $promotion->customer,

                'key_location' => $data['key']['Location'][0] ?? $promotion->key_location,
                'key_customer' => $data['key']['Customer'][0] ?? $promotion->key_customer,

                'key_item' => $data['key']['Item'][0] ?? $promotion->key_item,

                'updated_user' => auth()->id(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | 2. SOFT DELETE OLD CHILD DATA (REPLACE MODE)
        |--------------------------------------------------------------------------
        */
            PromotionDetail::where('header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            PromotionOfferItem::where('promotion_header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            PromotionalSlab::where('promotion_header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            /*
        |--------------------------------------------------------------------------
        | 3. INSERT NEW DETAILS + OFFER ITEMS
        |--------------------------------------------------------------------------
        */
            $details     = $data['promotion_details'] ?? [];
            $offers      = $data['offer_items'] ?? [];
            $percentages = $data['percentage_discounts'] ?? [];

            foreach ($details as $detail) {

                PromotionDetail::create([
                    'header_id'    => $promotion->id,
                    'from_qty'     => (int) $detail['from_qty'],
                    'to_qty'       => (int) $detail['to_qty'],
                    'free_qty'     => (int) $detail['free_qty'],
                    'created_user' => auth()->id(),
                ]);
            }

            foreach ($offers as $offer) {

                if (!empty($percentages)) {
                    foreach ($percentages as $percentage) {

                        PromotionOfferItem::create([
                            'promotion_header_id'   => $promotion->id,
                            'offer_item_id'         => $offer['item_id'],
                            'uom'                   => $offer['uom'],
                            'percentage_item_id'     => $percentage['percentage_item_id'],
                            'percentage_category_id' => $percentage['percentage_item_category'],
                            'percentage_uom'         => $offer['uom'],
                            'created_user'           => auth()->id(),
                        ]);
                    }
                } else {

                    PromotionOfferItem::create([
                        'promotion_header_id' => $promotion->id,
                        'offer_item_id'       => $offer['item_id'],
                        'uom'                 => $offer['uom'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 4. INSERT PROMOTIONAL SLABS (ONLY IF SLAB)
        |--------------------------------------------------------------------------
        */
            if ($promotion->bundle_combination === 'slab') {

                foreach ($percentages as $percentage) {

                    PromotionalSlab::create([
                        'promotion_header_id' => $promotion->id,
                        'category'            => $percentage['percentage_item_category'],
                        'item_id'             => $percentage['percentage_item_id'],
                        'percentage'          => $percentage['percentage'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $promotion->load([
                'promotionDetails',
                'offerItems',
                'promotionalSlabs',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('[PROMOTION UPDATE FAILED]', [
                'uuid'    => $uuid,
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);

            throw new \Exception(
                'Failed to update promotion. Please try again later.',
                500,
                $e
            );
        }
    }



    /**
     * LIST
  
     * LIST PROMOTIONS
     */
    public function list(array $filters)
    {
        try {
            $query = PromotionHeader::with('promotionDetails', 'offerItems', 'promotionalSlabs')
                ->orderByDesc('id');

            if (!empty($filters['id'])) {
                $query->where('id', $filters['id']);
            }

            if (!empty($filters['promtion_name'])) {
                $query->where('promotion_name', 'ILIKE', '%' . $filters['promtion_name'] . '%');
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $limit = $filters['limit'] ?? 50;

            return $query->paginate($limit);
        } catch (\Throwable $e) {

            \Log::error('[PROMOTION LIST FAILED]', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to fetch promotion list. Please try again later.',
                500,
                $e
            );
        }
    }


    /**
     * SHOW
     */
    public function show(string $uuid): ?PromotionHeader
    {
        return PromotionHeader::with('promotionDetails', 'offerItems', 'promotionalSlabs')
            ->where('uuid', $uuid)
            ->first();
    }


    /**
     * DELETE (Soft Delete)
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $promotion = PromotionHeader::findOrFail($id);

            $promotion->update([
                'deleted_user' => auth()->id(),
            ]);

            return $promotion->delete();
        });
    }

    /**
     * OSA CODE GENERATOR
     */
    private function generateOsaCode(): string
    {
        $last = PromotionHeader::withTrashed()
            ->orderByDesc('id')
            ->first();

        $next = $last
            ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1
            : 1;

        return 'PR' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }



    public function getByWarehouseId(int $warehouseId)
    {
        /**
         * 🔹 STEP 1: Fetch Warehouse
         */
        $warehouse = Warehouse::query()
            ->select('id', 'area_id', 'region_id', 'company')
            ->where('id', $warehouseId)
            ->first();

        if (! $warehouse) {
            return collect();
        }

        $today = Carbon::today()->toDateString();

        /**
         * 🔹 STEP 2: Promotion Query
         */
        return PromotionHeader::query()
            ->whereNull('deleted_at')
            ->where('status', 1)

            // 🔹 Date validity
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)

            // 🔹 Location match
            ->where(function ($q) use ($warehouse) {

                // Warehouse
                $q->orWhere(function ($w) use ($warehouse) {
                    $w->where('key_location', 'Warehouse')
                        ->whereRaw(
                            "? = ANY (string_to_array(location, ','))",
                            [(string) $warehouse->id]
                        );
                });

                // Area
                if ($warehouse->area_id) {
                    $q->orWhere(function ($a) use ($warehouse) {
                        $a->where('key_location', 'Area')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->area_id]
                            );
                    });
                }

                // Region
                if ($warehouse->region_id) {
                    $q->orWhere(function ($r) use ($warehouse) {
                        $r->where('key_location', 'Region')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->region_id]
                            );
                    });
                }

                // Company
                if ($warehouse->company) {
                    $q->orWhere(function ($c) use ($warehouse) {
                        $c->where('key_location', 'Company')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->company]
                            );
                    });
                }
            })

            /**
             * 🔹 Eager Load Relations
             */
            ->with([
                'promotionDetails' => fn($q) => $q->whereNull('deleted_at'),

                'offerItems' => fn($q) => $q
                    ->whereNull('deleted_at')
                    ->with(
                        'offerItem:id,name,erp_code',
                        'percentageItem:id,name,erp_code'
                    ),

                'promotionalSlabs' => fn($q) => $q->whereNull('deleted_at'),
            ])

            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();
    }
    // public function getByWarehouseId(int $warehouseId)
    // {
    //     /**
    //      * 🔹 STEP 1: Fetch Warehouse (Area / Region / Company)
    //      */
    //     $warehouse = Warehouse::query()
    //         ->select('id', 'area_id', 'region_id', 'company') // company column exists in your table
    //         ->where('id', $warehouseId)
    //         ->first();

    //     if (! $warehouse) {
    //         return collect();
    //     }

    //     $today = Carbon::today()->toDateString();

    //     /**
    //      * 🔹 STEP 2: Promotion Query (ONLY CURRENT PROMOTIONS)
    //      */
    //     return PromotionHeader::query()
    //         ->whereNull('deleted_at')
    //         ->where('status', 1)

    //         // 🔹 Date validity
    //         ->whereDate('from_date', '<=', $today)
    //         ->whereDate('to_date', '>=', $today)

    //         // 🔹 Location + key_location STRICT MATCH
    //         ->where(function ($q) use ($warehouse) {

    //             // 🔹 Warehouse-level promotions
    //             $q->orWhere(function ($w) use ($warehouse) {
    //                 $w->where('key_location', 'Warehouse')
    //                     ->whereRaw(
    //                         "? = ANY (string_to_array(location, ','))",
    //                         [(string) $warehouse->id]
    //                     );
    //             });

    //             // 🔹 Area-level promotions
    //             if ($warehouse->area_id) {
    //                 $q->orWhere(function ($a) use ($warehouse) {
    //                     $a->where('key_location', 'Area')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->area_id]
    //                         );
    //                 });
    //             }

    //             // 🔹 Region-level promotions
    //             if ($warehouse->region_id) {
    //                 $q->orWhere(function ($r) use ($warehouse) {
    //                     $r->where('key_location', 'Region')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->region_id]
    //                         );
    //                 });
    //             }

    //             // 🔹 Company-level promotions
    //             if ($warehouse->company) {
    //                 $q->orWhere(function ($c) use ($warehouse) {
    //                     $c->where('key_location', 'Company')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->company]
    //                         );
    //                 });
    //             }
    //         })

    //         ->with([
    //             'promotionDetails' => fn($q) => $q->whereNull('deleted_at'),
    //             'offerItems'       => fn($q) => $q->whereNull('deleted_at'),
    //             'promotionalSlabs' => fn($q) => $q->whereNull('deleted_at'),
    //         ])

    //         ->orderByDesc('id')
    //         ->get()
    //         ->unique('id')
    //         ->values();
    // }
}
