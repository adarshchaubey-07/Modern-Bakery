<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\AgentCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\DataAccessHelper;
use Throwable;
use Exception;
use Error;

class AgentCustomerService
{

    // public function getAll(int $perPage = 10, array $filters = [])
    // {
    //     try {
    //         $query = AgentCustomer::with([
    //             'customertype:id,code,name',
    //             'region:id,region_code,region_name',
    //             'area:id,area_code,area_name',
    //             'outlet_channel:id,outlet_channel_code,outlet_channel',
    //             'category:id,customer_category_code,customer_category_name',
    //             'subcategory:id,customer_category_id,customer_sub_category_name,customer_sub_category_code',
    //             'route:id,route_code,route_name',
    //             'getWarehouse:id,warehouse_code,warehouse_name'
    //         ])->latest();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 // Text search fields (case-insensitive)
    //                 if (in_array($field, ['osa_code', 'name', 'owner_name', 'business_name'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 }
    //                 // Direct FK or attribute filters
    //                 elseif (in_array($field, [
    //                     'route_id',
    //                     'outlet_channel_id',
    //                     'category_id',
    //                     'subcategory_id',
    //                     'region_id',
    //                     'area_id',
    //                     'status',
    //                     'warehouse'
    //                 ])) {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         // Example of filtering by related model's attribute (optional, if needed)
    //         if (!empty($filters['warehouse_name'])) {
    //             $query->whereHas('getWarehouse', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(warehouse_name) LIKE ?', ['%' . strtolower($filters['warehouse_name']) . '%']);
    //             });
    //         }
    //         if (!empty($filters['route_name'])) {
    //             $query->whereHas('route', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(route_name) LIKE ?', ['%' . strtolower($filters['route_name']) . '%']);
    //             });
    //         }
    //         if (!empty($filters['category_name'])) {
    //             $query->whereHas('category', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(customer_category_name) LIKE ?', ['%' . strtolower($filters['category_name']) . '%']);
    //             });
    //         }
    //         if (!empty($filters['subcategory_name'])) {
    //             $query->whereHas('subcategory', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(customer_sub_category_name) LIKE ?', ['%' . strtolower($filters['subcategory_name']) . '%']);
    //             });
    //         }
    //         if (!empty($filters['outlet_channel_name'])) {
    //             $query->whereHas('outlet_channel', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(outlet_channel) LIKE ?', ['%' . strtolower($filters['outlet_channel_name']) . '%']);
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (\Throwable $e) {
    //         \Log::error("Failed to fetch customers", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'filters' => $filters,
    //         ]);
    //         throw new \Exception("Something went wrong while fetching customers, please try again.");
    //     }
    // }
    public function getAll(int $perPage = 50, array $filters = [], $type = null)
    {
        // dd($filters);
        try {
            $user = auth()->user();
            $query = AgentCustomer::with([
                'customertype:id,code,name',
                'region:id,region_code,region_name',
                'area:id,area_code,area_name',
                'outlet_channel:id,outlet_channel_code,outlet_channel',
                'category:id,customer_category_code,customer_category_name',
                'subcategory:id,customer_category_id,customer_sub_category_name,customer_sub_category_code',
                'route:id,route_code,route_name',
                'getWarehouse:id,warehouse_code,warehouse_name'
            ])->latest();
            $query = DataAccessHelper::filterAgentCustomers($query, $user);
            if (!empty($type)) {
                if ($type == 1) {
                    $query->where('customer_type', 20);
                } elseif ($type == 2) {
                    $query->where('customer_type', '!=', 20);
                }
            }
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code', 'name', 'owner_name', 'business_name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } elseif (in_array($field, [
                        'route_id',
                        'outlet_channel_id',
                        'category_id',
                        'subcategory_id',
                        'region_id',
                        'area_id',
                        'status',
                        'warehouse'
                    ])) {
                        $query->where($field, $value);
                    }
                }
            }
            if (!empty($filters['warehouse_name'])) {
                $query->whereHas('getWarehouse', function ($q) use ($filters) {
                    $q->whereRaw('LOWER(warehouse_name) LIKE ?', ['%' . strtolower($filters['warehouse_name']) . '%']);
                });
            }
            if (!empty($filters['route_name'])) {
                $query->whereHas('route', function ($q) use ($filters) {
                    $q->whereRaw('LOWER(route_name) LIKE ?', ['%' . strtolower($filters['route_name']) . '%']);
                });
            }
            if (!empty($filters['category_name'])) {
                $query->whereHas('category', function ($q) use ($filters) {
                    $q->whereRaw('LOWER(customer_category_name) LIKE ?', ['%' . strtolower($filters['category_name']) . '%']);
                });
            }
            if (!empty($filters['subcategory_name'])) {
                $query->whereHas('subcategory', function ($q) use ($filters) {
                    $q->whereRaw('LOWER(customer_sub_category_name) LIKE ?', ['%' . strtolower($filters['subcategory_name']) . '%']);
                });
            }
            if (!empty($filters['outlet_channel_name'])) {
                $query->whereHas('outlet_channel', function ($q) use ($filters) {
                    $q->whereRaw('LOWER(outlet_channel) LIKE ?', ['%' . strtolower($filters['outlet_channel_name']) . '%']);
                });
            }

            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            \Log::error("Failed to fetch customers", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);
            throw new \Exception("Something went wrong while fetching customers, please try again.");
        }
    }


    public function getList(int $perPage = 10, array $filters = [], $type = null)
    {
        try {
            // 🔹 Base query
            $query = AgentCustomer::select('id', 'osa_code', 'name')->latest();

            // 🧩 Step 1: Apply 'type' logic first
            if (!empty($type)) {
                if ($type == 1) {
                    // type=1 → customer_type = 20
                    $query->where('customer_type', 20);
                } elseif ($type == 2) {
                    // type=2 → customer_type != 20
                    $query->where('customer_type', '!=', 20);
                }
            }

            // 🔹 Step 2: Exclude customers already present in route_visit
            $usedCustomerIds = DB::table('route_visit')->pluck('customer_id')->toArray();
            if (!empty($usedCustomerIds)) {
                $query->whereNotIn('id', $usedCustomerIds);
            }

            // 🔍 Step 3: Apply filters (osa_code or name)
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code', 'name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    }
                }
            }

            // 📄 Return paginated result
            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching agent customer list: ' . $e->getMessage());
            throw new \Exception("Something went wrong while fetching customer list.");
        }
    }





    /**
     * Generate unique customer code
     */
    public function generateCode(): string
    {
        do {
            $last = AgentCustomer::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'AC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (AgentCustomer::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    /**
     * Create a new customer
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            if (!isset($data['osa_code']) || empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }
            $customer = AgentCustomer::create($data);

            DB::commit();
            return $customer;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Customer creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    /**
     * Find customer by UUID
     */
    // public function findByUuid(string $uuid): ?AgentCustomer
    // {
    //     if (!Str::isUuid($uuid)) {
    //         return null;
    //     }

    //     return AgentCustomer::where('uuid', $uuid)->first();
    // }
    public function findByUuid(string $uuid): ?AgentCustomer
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }
// dd($uuid);
        return AgentCustomer::with([
            'customertype:id,code,name',
            'region:id,region_code,region_name',
            'area:id,area_code,area_name',
            'outlet_channel:id,outlet_channel_code,outlet_channel',
            'category:id,customer_category_code,customer_category_name',
            'subcategory:id,customer_category_id,customer_sub_category_name,customer_sub_category_code',
            'route:id,route_code,route_name',
            'getWarehouse:id,warehouse_code,warehouse_name'
        ])->where('uuid', $uuid)->first();
    }

    // public function updateByUuid(string $uuid, array $validated)
    // {
    //     $customer = $this->findByUuid($uuid);
    //     if (!$customer) {
    //         throw new Exception("Customer not found or invalid UUID: {$uuid}");
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $data['updated_user'] = Auth::id();
    //         $customer->update($data);
    //         DB::commit();

    //         return $customer->fresh();
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

    //         Log::error("Customer update failed", [
    //             'error'   => $e->getMessage(),
    //             'uuid'    => $uuid,
    //             'payload' => $data,
    //         ]);

    //         throw new Exception($friendlyMessage, 0, $e);
    //     }
    // }
    public function updateByUuid(string $uuid, array $validated)
    {
        $customer = $this->findByUuid($uuid);
        if (!$customer) {
            throw new Exception("Customer not found or invalid UUID: {$uuid}");
        }
        DB::beginTransaction();
        try {
            $data = array_merge($validated, [
                'updated_user' => Auth::id(),
            ]);
            // dd($data);
            $customer->update($data);

            DB::commit();

            // Return fresh copy
            return $customer->fresh();
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("Customer update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $validated,
            ]);
            throw new Exception($friendlyMessage, 0, $e);
        }
    }

    /**
     * Soft delete customer by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $customer = $this->findByUuid($uuid);
        if (!$customer) {
            throw new \Exception("Customer not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $customer->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Customer delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function globalSearchAgentCustomer($perPage = 50, $keyword = null, $warehouseId = null, $status = null)
{
    try {
        $query = AgentCustomer::with([
            'route:id,route_code,route_name',
            'category:id,customer_category_name,customer_category_code',
            'subcategory:id,customer_sub_category_name,customer_sub_category_code',
            'outlet_channel:id,outlet_channel',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
            'getWarehouse:id,warehouse_name,warehouse_code'
        ])->latest();

        /**
         * 🔹 STATUS FILTER (DEFAULT = 1)
         */
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->where('status', 1);
        }

        /**
         * 🔹 WAREHOUSE FILTER
         */
        if (!empty($warehouseId)) {
            $query->where('warehouse', $warehouseId);
        }

        /**
         * 🔹 GLOBAL KEYWORD SEARCH
         */
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {

                // Text fields
                $searchableFields = [
                    'osa_code',
                    'name',
                    'whatsapp_no',
                    'contact_no',
                    'contact_no2',
                    'street',
                    'town',
                    'landmark',
                    'district',
                    'creditday',
                    'vat_no',
                    'longitude',
                    'latitude',
                    'qr_code',
                    'owner_name',
                    'email',
                    'language',
                    'fridge'
                ];

                foreach ($searchableFields as $field) {
                    $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
                }

                // Numeric fields
                $numericFields = [
                    'id',
                    'customer_type',
                    'route_id',
                    'is_whatsapp',
                    'buyertype',
                    'outlet_channel_id',
                    'category_id',
                    'subcategory_id',
                    'warehouse',
                    'credit_limit',
                    'payment_type',
                    'created_user',
                    'updated_user'
                ];

                foreach ($numericFields as $field) {
                    $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
                }

                // Timestamp fields
                foreach (['created_at', 'updated_at', 'deleted_at'] as $field) {
                    $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
                }

                // Relations
                $q->orWhereHas('route', fn ($qr) =>
                    $qr->where('route_code', 'ILIKE', "%{$keyword}%")
                       ->orWhere('route_name', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('category', fn ($qr) =>
                    $qr->where('customer_category_name', 'ILIKE', "%{$keyword}%")
                       ->orWhere('customer_category_code', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('subcategory', fn ($qr) =>
                    $qr->where('customer_sub_category_name', 'ILIKE', "%{$keyword}%")
                       ->orWhere('customer_sub_category_code', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('outlet_channel', fn ($qr) =>
                    $qr->where('outlet_channel', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('getWarehouse', fn ($qr) =>
                    $qr->where('warehouse_name', 'ILIKE', "%{$keyword}%")
                       ->orWhere('warehouse_code', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('createdBy', fn ($qr) =>
                    $qr->where('name', 'ILIKE', "%{$keyword}%")
                       ->orWhere('username', 'ILIKE', "%{$keyword}%")
                );

                $q->orWhereHas('updatedBy', fn ($qr) =>
                    $qr->where('name', 'ILIKE', "%{$keyword}%")
                       ->orWhere('username', 'ILIKE', "%{$keyword}%")
                );
            });
        }

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        throw new \Exception("Failed to search agent customers: " . $e->getMessage());
    }
}


    // public function globalSearchAgentCustomer($perPage = 10, $keyword = null)
    // {
    //     try {
    //         $query = AgentCustomer::with([
    //             'route:id,route_code,route_name',
    //             'category:id,customer_category_name,customer_category_code',
    //             'subcategory:id,customer_sub_category_name, customer_sub_category_code',
    //             'outlet_channel:id,outlet_channel',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username',
    //             'getWarehouse:id,warehouse_name,warehouse_code'
    //         ]);
    //         if (!empty($warehouseId)) {
    //             $query->where('warehouse', $warehouseId);
    //         }

    //         if (!empty($keyword)) {
    //             $query->where(function ($q) use ($keyword) {

    //                 // Searchable fields in agent_customer table
    //                 $searchableFields = [
    //                     'osa_code',
    //                     'name',
    //                     'whatsapp_no',
    //                     'contact_no',
    //                     'contact_no2',
    //                     'street',
    //                     'town',
    //                     'landmark',
    //                     'district',
    //                     'creditday',
    //                     'vat_no',
    //                     'longitude',
    //                     'latitude',
    //                     'warehouse',
    //                     'qr_code',
    //                     'owner_name',
    //                     'email',
    //                     'language',
    //                     'fridge'
    //                 ];
    //                 foreach ($searchableFields as $field) {
    //                     $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
    //                 }

    //                 // Numeric / smallint fields
    //                 $numericFields = ['id', 'customer_type', 'route_id', 'is_whatsapp', 'buyertype', 'outlet_channel_id', 'category_id', 'subcategory_id', 'status', 'warehouse', 'credit_limit', 'payment_type', 'created_user', 'updated_user'];
    //                 foreach ($numericFields as $field) {
    //                     $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
    //                 }

    //                 // Timestamp fields
    //                 $timestampFields = ['created_at', 'updated_at', 'deleted_at'];
    //                 foreach ($timestampFields as $field) {
    //                     $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
    //                 }

    //                 // Search in related models
    //                 $q->orWhereHas('route', function ($qr) use ($keyword) {
    //                     $qr->where('route_code', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('route_name', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('category', function ($qr) use ($keyword) {
    //                     $qr->where('customer_category_name', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('customer_category_code', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('subcategory', function ($qr) use ($keyword) {
    //                     $qr->where('customer_sub_category_name', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('customer_sub_category_code', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('outlet_channel', function ($qr) use ($keyword) {
    //                     $qr->where('outlet_channel', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('getWarehouse', function ($qr) use ($keyword) {
    //                     $qr->where('warehouse_name', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('warehouse_code', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('createdBy', function ($qr) use ($keyword) {
    //                     $qr->where('name', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('username', 'ILIKE', "%{$keyword}%");
    //                 });

    //                 $q->orWhereHas('updatedBy', function ($qr) use ($keyword) {
    //                     $qr->where('name', 'ILIKE', "%{$keyword}%")
    //                         ->orWhere('username', 'ILIKE', "%{$keyword}%");
    //                 });
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to search agent customers: " . $e->getMessage());
    //     }
    // }

    // public function globalSearchAgentCustomer($perPage = 10, $keyword = null)
    // {
    //     try {
    //         $query = AgentCustomer::query()
    //             ->select('agent_customers.*')
    //             ->leftJoin('tbl_route', 'agent_customers.route_id', '=', 'tbl_route.id')
    //             ->leftJoin('tbl_warehouse', 'agent_customers.warehouse', '=', 'tbl_warehouse.id')
    //             ->leftJoin('customer_categories as category', 'agent_customers.category_id', '=', 'category.id')
    //             ->leftJoin('customer_sub_categories as subcategory', 'agent_customers.subcategory_id', '=', 'subcategory.id')
    //             ->leftJoin('outlet_channel', 'agent_customers.outlet_channel_id', '=', 'outlet_channel.id')
    //             ->leftJoin('users as createdBy', 'agent_customers.created_user', '=', 'createdBy.id')
    //             ->leftJoin('users as updatedBy', 'agent_customers.updated_user', '=', 'updatedBy.id');

    //         if (!empty($keyword)) {
    //             $query->where(function ($q) use ($keyword) {
    //                 $q->where('agent_customers.name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('agent_customers.osa_code', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('agent_customers.owner_name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('agent_customers.contact_no', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('tbl_route.route_name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('tbl_warehouse.warehouse_name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('category.customer_category_name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('subcategory.customer_sub_category_name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('outlet_channel.outlet_channel', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('createdBy.name', 'ILIKE', "%{$keyword}%")
    //                     ->orWhere('updatedBy.name', 'ILIKE', "%{$keyword}%");
    //             });
    //         }

    //         // Eager load relationships to avoid N+1 queries
    //         $query->with([
    //             'route:id,route_code,route_name',
    //             'category:id,customer_category_name,customer_category_code',
    //             'subcategory:id,customer_sub_category_name,customer_sub_category_code',
    //             'outlet_channel:id,outlet_channel',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username',
    //             'getWarehouse:id,warehouse_name,warehouse_code'
    //         ]);

    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to search agent customers: " . $e->getMessage());
    //     }
    // }
}
