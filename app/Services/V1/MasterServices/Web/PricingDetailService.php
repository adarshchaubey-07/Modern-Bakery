<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\PricingDetail;
use App\Models\PricingHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PricingDetailService
{
    public function all(array $filters = [], int $perPage = 10)
    {
        $query = PricingDetail::query();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, $value);
            }
        }

        return $query->paginate($perPage);
    }

    // public function generateOsaCode(): string
    // {
    //     do {
    //         $last = PricingDetail::withTrashed()->latest('id')->first();
    //         $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
    //         $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
    //     } while (PricingDetail::withTrashed()->where('osa_code', $osa_code)->exists());

    //     return $osa_code;
    // }

    // public function create(array $data): PricingDetail
    // {
    //     DB::beginTransaction();
    //     try {
    //         $data = array_merge($data, [
    //             'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //             'osa_code' => $this->generateOsaCode(),
    //         ]);
    //         $pricingDetail = PricingDetail::create($data);
    //         DB::commit();
    //         return $pricingDetail;
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('PricingDetail creation failed', ['error' => $e->getMessage(), 'data' => $data]);
    //         throw new \Exception('Failed to create pricing detail: ' . $e->getMessage(), 0, $e);
    //     }
    // }


    public function generateCode(): string
    {
        do {
            $lastPrice = PricingHeader::withTrashed()->latest('id')->first();
            $nextNumber = $lastPrice
                ? ((int) preg_replace('/\D/', '', $lastPrice->code)) + 1
                : 1;

            $code = 'PH' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (PricingHeader::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {

            $data['description'] = isset($data['description']) && is_array($data['description'])
                ? $data['description']
                : json_decode($data['description'], true);

            // ✅ Convert array fields to comma-separated strings (match actual array keys)
            $data['company_id'] = isset($data['company_id']) ? implode(',', $data['company_id']) : null;
            $data['region_id'] = isset($data['region_id']) ? implode(',', $data['region_id']) : null;
            $data['area_id'] = isset($data['area_id']) ? implode(',', $data['area_id']) : null;
            $data['route_id'] = isset($data['route_id']) ? implode(',', $data['route_id']) : null;
            $data['warehouse_id'] = isset($data['warehouse_id']) ? implode(',', $data['warehouse_id']) : null;
            $data['outlet_channel_id'] = isset($data['outlet_channel_id']) ? implode(',', $data['outlet_channel_id']) : null;
            $data['customer_category_id'] = isset($data['customer_category_id']) ? implode(',', $data['customer_category_id']) : null;
            $data['customer_id'] = isset($data['customer_id']) ? implode(',', $data['customer_id']) : null;
            $data['item_category_id'] = isset($data['item_category_id']) ? implode(',', $data['item_category_id']) : null;
            $data['item_id'] = isset($data['item_id']) ? implode(',', $data['item_id']) : null;

            // dd($data);

            // // 🔹 Convert arrays to comma-separated strings for DB storage
            // $multiFields = [
            //     'warehouse_id',
            //     'item_type',
            //     'company_id',
            //     'region_id',
            //     'area_id',
            //     'route_id',
            //     'item_id',
            //     'item_category_id',
            //     'customer_id',
            //     'customer_category_id',
            //     'customer_type_id',
            //     'outlet_channel_id'
            // ];

            // foreach ($multiFields as $field) {
            //     if (isset($data[$field]) && is_array($data[$field])) {
            //         $data[$field] = implode(',', $data[$field]);
            //     }
            // }

            // dd($data);
            // 1️⃣ Create header
            $header = PricingHeader::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $data['name'],
                'code' => $this->generateCode(),
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'apply_on' => $data['apply_on'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'item_type' => $data['item_type'] ?? null,
                'status' => $data['status'] ?? 1,
                'applicable_for' => $data['applicable_for'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'area_id' => $data['area_id'] ?? null,
                'route_id' => $data['route_id'] ?? null,
                'item_category_id' => $data['item_category_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'item_id' => $data['item_id'] ?? null,
                'customer_category_id' => $data['customer_category_id'] ?? null,
                'customer_type_id' => $data['customer_type_id'] ?? null,
                'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
                'created_user' => $data['created_user'] ?? 1,
                'updated_user' => $data['updated_user'] ?? null,
            ]);

            foreach ($data['details'] ?? [] as $detail) {

            $detailModel = new PricingDetail();
            $detailModel->uuid = Str::uuid()->toString();
            $detailModel->header_id = $header->id;
            $detailModel->uom_id = $detail['uom_id'] ?? null;
            $detailModel->item_id = $detail['item_id'] ?? null;
            $detailModel->price = $detail['price'] ?? 0.00;
            $detailModel->status = $detail['status'] ?? $data['status'] ?? 1;
            $detailModel->created_user = $data['created_user'] ?? 1;
            $detailModel->save();

            $detailModel->osa_code = 'PD' . str_pad($detailModel->id, 3, '0', STR_PAD_LEFT);
            $detailModel->name = 'Item ' . $detailModel->item_id;
            $detailModel->save();
        }

            DB::commit();

            return $header->load('details.item');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pricing create failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new \Exception('Failed to create pricing: ' . $e->getMessage(), 0, $e);
        }
    }

    // public function create(array $data)
    // {
    //     dd($data);
    //     DB::beginTransaction();
    //     try {
    //         // 🔹 Convert array fields to comma-separated strings
    //         $multiFields = [
    //             'warehouse_id',
    //             'item_type',
    //             'company_id',
    //             'region_id',
    //             'area_id',
    //             'route_id',
    //             'item_id',
    //             'item_category_id',
    //             'customer_id',
    //             'customer_category_id',
    //             'customer_type_id',
    //             'outlet_channel_id'
    //         ];

    //         foreach ($multiFields as $field) {
    //             if (isset($data[$field]) && is_array($data[$field])) {
    //                 $data[$field] = implode(',', $data[$field]);
    //             }
    //         }

    //         // 🧾 Create header
    //         $header = PricingHeader::create([
    //             'uuid'                  => Str::uuid()->toString(),
    //             'name'                  => $data['name'],
    //             'code'                  => $this->generateCode(),
    //             'description'           => is_array($data['description'])
    //                 ? json_encode($data['description'])
    //                 : $data['description'],
    //             'start_date'            => $data['start_date'] ?? null,
    //             'end_date'              => $data['end_date'] ?? null,
    //             'apply_on'              => $data['apply_on'] ?? null,
    //             'warehouse_id'          => $data['warehouse_id'] ?? null,
    //             'item_type'             => $data['item_type'] ?? null,
    //             'status'                => $data['status'] ?? 1,
    //             'company_id'            => $data['company_id'] ?? null,
    //             'region_id'             => $data['region_id'] ?? null,
    //             'area_id'               => $data['area_id'] ?? null,
    //             'route_id'              => $data['route_id'] ?? null,
    //             'item_category_id'      => $data['item_category_id'] ?? null,
    //             'customer_id'           => $data['customer_id'] ?? null,
    //             'item_id'               => $data['item_id'] ?? null,
    //             'customer_category_id'  => $data['customer_category_id'] ?? null,
    //             'customer_type_id'      => $data['customer_type_id'] ?? null,
    //             'outlet_channel_id'     => $data['outlet_channel_id'] ?? null,
    //             'created_user'          => $data['created_user'] ?? 1,
    //             'updated_user'          => $data['updated_user'] ?? null,
    //         ]);

    //         // 🔢 Generate sequential osa_code for details
    //         $last = PricingDetail::withTrashed()->latest('id')->first();
    //         $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;

    //         // 🧱 Prepare details
    //         $details = collect($data['details'] ?? [])->map(function ($detail) use ($data, $header, &$next) {
    //             $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
    //             $next++;

    //             return [
    //                 'uuid'           => Str::uuid()->toString(),
    //                 'osa_code'       => $osa_code,
    //                 'name'           => isset($detail['item_id'])
    //                     ? 'Item ' . $detail['item_id']
    //                     : ($detail['name'] ?? null),
    //                 'header_id'      => $header->id,
    //                 'item_id'        => $detail['item_id'] ?? null,
    //                 'buom_ctn_price' => $detail['buom_ctn_price'] ?? 0.00,
    //                 'auom_pc_price'  => $detail['auom_pc_price'] ?? 0.00,
    //                 'status'         => $detail['status'] ?? $data['status'] ?? 1,
    //                 'created_user'   => $data['created_user'] ?? 1,
    //             ];
    //         })->toArray();

    //         // 💾 Bulk insert details
    //         $header->details()->createMany($details);

    //         DB::commit();

    //         // 🔄 Return header with details and item info
    //         return $header->load('details.item');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Pricing create failed', [
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);
    //         throw new \Exception('Failed to create pricing: ' . $e->getMessage(), 0, $e);
    //     }
    // }

    public function findByUuid(string $uuid): ?PricingDetail
    {
        return PricingDetail::where('uuid', $uuid)->first();
    }

    // public function updateByUuid(string $uuid, array $data): PricingHeader
    // {
    //     // Find the header by UUID
    //     $header = PricingHeader::where('uuid', $uuid)->first();

    //     if (!$header) {
    //         throw new \Exception("Pricing header not found");
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // 🔹 Convert arrays to comma-separated strings for DB storage
    //         $multiFields = [
    //             'warehouse_id',
    //             'item_type',
    //             'company_id',
    //             'region_id',
    //             'area_id',
    //             'route_id',
    //             'item_id',
    //             'item_category_id',
    //             'customer_id',
    //             'customer_category_id',
    //             'customer_type_id',
    //             'outlet_channel_id'
    //         ];

    //         foreach ($multiFields as $field) {
    //             if (isset($data[$field]) && is_array($data[$field])) {
    //                 $data[$field] = implode(',', $data[$field]);
    //             }
    //         }

    //         // 🔹 Update header fields
    //         $header->update([
    //             'name' => $data['name'] ?? $header->name,
    //             'code' => $data['code'] ?? $header->code,
    //             'description' => $data['description'] ?? $header->description,
    //             'start_date' => $data['start_date'] ?? $header->start_date,
    //             'end_date' => $data['end_date'] ?? $header->end_date,
    //             'apply_on' => $data['apply_on'] ?? $header->apply_on,
    //             'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
    //             'item_type' => $data['item_type'] ?? $header->item_type,
    //             'status' => $data['status'] ?? $header->status,
    //             'company_id' => $data['company_id'] ?? $header->company_id,
    //             'region_id' => $data['region_id'] ?? $header->region_id,
    //             'area_id' => $data['area_id'] ?? $header->area_id,
    //             'item_id' => $data['item_id'] ?? $header->item_id,
    //             'route_id' => $data['route_id'] ?? $header->route_id,
    //             'item_category_id' => $data['item_category_id'] ?? $header->item_category_id,
    //             'customer_id' => $data['customer_id'] ?? $header->customer_id,
    //             'customer_category_id' => $data['customer_category_id'] ?? $header->customer_category_id,
    //             'customer_type_id' => $data['customer_type_id'] ?? $header->customer_type_id,
    //             'outlet_channel_id' => $data['outlet_channel_id'] ?? $header->outlet_channel_id,
    //             'updated_user' => $data['updated_user'] ?? $header->updated_user,
    //         ]);

    //         // 🔹 Update details
    //         if (!empty($data['details'])) {
    //             // Delete old details (optional: or you can update individually)
    //             $header->details()->delete();

    //             // Generate new osa_code sequence
    //             $last = PricingDetail::withTrashed()->latest('id')->first();
    //             $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;

    //             $details = collect($data['details'])->map(function ($detail) use ($header, &$next, $data) {
    //                 $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
    //                 $next++;

    //                 return [
    //                     'uuid' => Str::uuid()->toString(),
    //                     'osa_code' => $osa_code,
    //                     'name' => isset($detail['item_id']) ? 'Item ' . $detail['item_id'] : null,
    //                     'header_id' => $header->id,
    //                     'item_id' => $detail['item_id'] ?? null,
    //                     'buom_ctn_price' => $detail['buom_ctn_price'] ?? 0.00,
    //                     'auom_pc_price' => $detail['auom_pc_price'] ?? 0.00,
    //                     'status' => $detail['status'] ?? $data['status'] ?? 1,
    //                     'created_user' => $data['updated_user'] ?? $header->updated_user ?? 1,
    //                 ];
    //             })->toArray();

    //             $header->details()->createMany($details);
    //         }

    //         DB::commit();

    //         // 🔹 Return updated header with details
    //         return $header->load('details.item');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Pricing header update failed', [
    //             'error' => $e->getMessage(),
    //             'uuid' => $uuid,
    //             'data' => $data
    //         ]);
    //         throw new \Exception('Failed to update pricing header: ' . $e->getMessage(), 0, $e);
    //     }
    // }

    public function updateByUuid(string $uuid, array $data): PricingHeader
    {
        $header = PricingHeader::where('uuid', $uuid)->first();

        if (!$header) {
            throw new \Exception("Pricing header not found");
        }

        DB::beginTransaction();

        try {
            $multiFields = [
                'warehouse_id',
                'item_type',
                'company_id',
                'region_id',
                'area_id',
                'route_id',
                'item_id',
                'item_category_id',
                'customer_id',
                'customer_category_id',
                'customer_type_id',
                'outlet_channel_id'
            ];

            foreach ($multiFields as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            $header->update([
                'name' => $data['name'] ?? $header->name,
                'description' => $data['description'] ?? $header->description,
                'start_date' => $data['start_date'] ?? $header->start_date,
                'end_date' => $data['end_date'] ?? $header->end_date,
                'apply_on' => $data['apply_on'] ?? $header->apply_on,
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'item_type' => $data['item_type'] ?? $header->item_type,
                'status' => $data['status'] ?? $header->status,
                'company_id' => $data['company_id'] ?? $header->company_id,
                'region_id' => $data['region_id'] ?? $header->region_id,
                'area_id' => $data['area_id'] ?? $header->area_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'item_category_id' => $data['item_category_id'] ?? $header->item_category_id,
                'item_id' => $data['item_id'] ?? $header->item_id,
                'customer_id' => $data['customer_id'] ?? $header->customer_id,
                'customer_category_id' => $data['customer_category_id'] ?? $header->customer_category_id,
                'customer_type_id' => $data['customer_type_id'] ?? $header->customer_type_id,
                'outlet_channel_id' => $data['outlet_channel_id'] ?? $header->outlet_channel_id,
                'updated_user' => $data['updated_user'] ?? $header->updated_user,
            ]);

            // if (!empty($data['details'])) {
            //     $header->details()->delete();

            //     $last = PricingDetail::withTrashed()->latest('id')->first();
            //     $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;

            //     $details = collect($data['details'])->map(function ($detail) use ($header, &$next, $data) {
            //         $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
            //         $next++;

            //         return [
            //             'uuid' => Str::uuid()->toString(),
            //             'osa_code' => $osa_code,
            //             'name' => isset($detail['item_id']) ? 'Item ' . $detail['item_id'] : null,
            //             'header_id' => $header->id,
            //             'item_id' => $detail['item_id'] ?? null,
            //             'uom_id'  => $detail['uom_id'] ?? null,
            //             'price' => $detail['price'] ?? 0.00,
            //             'status' => $detail['status'] ?? $data['status'] ?? 1,
            //             'created_user' => $data['updated_user'] ?? $header->updated_user ?? 1,
            //         ];
            //     })->toArray();

            //     $header->details()->createMany($details);
            // }
            if (!empty($data['details'])) {

                $existingDetailKeys = [];
                $existingDetails = PricingDetail::where('header_id', $header->id)->get();

                foreach ($data['details'] as $detail) {

                    $itemId = (int) $detail['item_id'];
                    $uomId  = (int) $detail['uom_id'];
                    $existing = $existingDetails->first(function ($row) use ($itemId, $uomId) {
                        return $row->item_id == $itemId && $row->uom_id == $uomId;
                    });

                    if ($existing) {
                        $existing->update([
                            'name'         => $detail['name'] ?? '',
                            'price'        => $detail['price'] ?? 0,
                            'status'       => $detail['status'] ?? 1,
                            'updated_user' => auth()->id(),
                        ]);

                        $existingDetailKeys[] = $existing->id;

                    } else {
                        $new = PricingDetail::create([
                            'uuid'         => Str::uuid(),
                            'header_id'    => $header->id,
                            'name'         => $detail['name'] ?? '',
                            'item_id'      => $itemId,
                            'uom_id'       => $uomId,
                            'price'        => $detail['price'] ?? 0,
                            'status'       => $detail['status'] ?? 1,
                            'created_user' => auth()->id(),
                            'updated_user' => auth()->id(),
                        ]);
                        $new->osa_code = 'PD' . str_pad($new->id, 3, '0', STR_PAD_LEFT);
                        $new->save();
 
                        $existingDetailKeys[] = $new->id; 
                    }
                }

                PricingDetail::where('header_id', $header->id)
                    ->whereNotIn('id', $existingDetailKeys)
                    ->delete();
            }

            DB::commit();

            return $header->load('details.item'); 
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pricing header update failed', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'data' => $data
            ]);
            throw new \Exception('Failed to update pricing header: ' . $e->getMessage(), 0, $e);
        }
    }



    public function deleteByUuid(string $uuid): void
    {
        $detail = $this->findByUuid($uuid);
        if (!$detail) {
            throw new \Exception("Pricing detail not found");
        }

        DB::beginTransaction();
        try {
            $detail->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('PricingDetail delete failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw new \Exception('Failed to delete pricing detail: ' . $e->getMessage(), 0, $e);
        }
    }
    // public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    // {
    //     try {
    //         $query = PricingDetail::query()->with([
    //             'item:id,code,name',
    //             'header:id,code,name',
    //         ]);

    //         if (!empty($searchTerm)) {

    //             $search = strtolower(trim($searchTerm));
    //             $like   = "%{$search}%";

    //             $query->where(function ($q) use ($like) {

    //                 $q->whereRaw('LOWER(osa_code::text) LIKE ?', [$like])
    //                   ->orWhereRaw('LOWER(status::text) LIKE ?', [$like])
    //                   ->orWhereRaw('id::text LIKE ?', [$like]);

    //                 $q->orWhereHas('item', function ($iq) use ($like) {
    //                     $iq->whereRaw('LOWER(code) LIKE ?', [$like])
    //                        ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
    //                 });

    //                 $q->orWhereHas('header', function ($hq) use ($like) {
    //                     $hq->whereRaw('LOWER(code) LIKE ?', [$like])
    //                        ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
    //                 });
    //             });
    //         }

    //         return $query
    //             ->orderByDesc('id')
    //             ->paginate($perPage);

    //     } catch (\Throwable $e) {
    //         throw new \Exception(
    //             'Failed to search pricing details: ' . $e->getMessage()
    //         );
    //     }
    // }
public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
{
    $query = PricingHeader::with([
        'details',
        'outletChannel',
        'customer'
    ])->whereNull('deleted_at');

    if ($searchTerm) {

        $query->where(function ($q) use ($searchTerm) {

            // Main table search
            $q->where('code', 'ILIKE', "%{$searchTerm}%")
                ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                ->orWhere('applicable_for', 'ILIKE', "%{$searchTerm}%")
                ->orWhereRaw('id::text ILIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('status::text ILIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('apply_on::text ILIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('start_date::text ILIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('end_date::text ILIKE ?', ["%{$searchTerm}%"]);

                $q->orWhereExists(function ($sub) use ($searchTerm) {
                    $sub->select(DB::raw(1))
                        ->from('outlet_channel')
                        ->whereNull('outlet_channel.deleted_at')
                        ->whereRaw("
                            outlet_channel.id = ANY(
                                string_to_array(pricing_headers.outlet_channel_id, ',')::int[]
                            )
                        ")
                        ->where(function ($channel) use ($searchTerm) {
                            $channel->where('outlet_channel_code', 'ILIKE', "%{$searchTerm}%")
                                    ->orWhere('outlet_channel', 'ILIKE', "%{$searchTerm}%");
                        });
                });

                $q->orWhereExists(function ($sub) use ($searchTerm) {
                    $sub->select(DB::raw(1))
                        ->from('agent_customers')
                        ->whereNull('agent_customers.deleted_at')
                        ->whereRaw("
                            agent_customers.id = ANY(
                                string_to_array(pricing_headers.customer_id, ',')::int[]
                            )
                        ")
                        ->where(function ($customer) use ($searchTerm) {
                            $customer->where('osa_code', 'ILIKE', "%{$searchTerm}%")
                                    ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                                    ->orWhere('owner_name', 'ILIKE', "%{$searchTerm}%");
                        });
                });
            $q->orWhereHas('details', function ($dq) use ($searchTerm) {
                $dq->whereNull('deleted_at')
                    ->where(function ($sub) use ($searchTerm) {
                        $sub->where('osa_code', 'ILIKE', "%{$searchTerm}%")
                            ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                            ->orWhereRaw('price::text ILIKE ?', ["%{$searchTerm}%"])
                            ->orWhereRaw('status::text ILIKE ?', ["%{$searchTerm}%"])
                            ->orWhereRaw('uom_id::text ILIKE ?', ["%{$searchTerm}%"])
                            ->orWhereRaw('item_id::text ILIKE ?', ["%{$searchTerm}%"]);
                    });
            });

        });
    }

    return $query
        ->orderByDesc('id')
        ->paginate($perPage);
}

}
