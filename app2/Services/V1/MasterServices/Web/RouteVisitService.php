<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\RouteVisit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Error;
use App\Models\Salesman;
use App\Helpers\DataAccessHelper;

class RouteVisitService
{
    public function getAll(int $perPage = 50, array $filters = [])
    {
        try {
            $user = auth()->user();
            $query = RouteVisit::query()->latest();
            $query = DataAccessHelper::filterRouteVisit($query, $user);
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $query->whereBetween('from_date', [
                    $filters['from_date'],
                    $filters['to_date']
                ]);
            } elseif (!empty($filters['from_date'])) {
                $query->where('from_date', '>=', $filters['from_date']);
            } elseif (!empty($filters['to_date'])) {
                $query->where('to_date', '<=', $filters['to_date']);
            }

            if (isset($filters['status'])) {
                $statuses = $filters['status'];

                if (is_string($statuses)) {
                    $statuses = explode(',', $statuses);
                }
                $statuses = array_map('intval', (array) $statuses);

                $query->whereIn('status', $statuses);
            }

            foreach ($filters as $field => $value) {
                if (!empty($value) && !in_array($field, ['from_date', 'to_date', 'status'])) {
                    if ($field === 'customer') {
                        $ids = is_array($value) ? $value : explode(',', $value);
                        $query->whereIn($field, $ids);
                    } elseif ($field === 'osa_code') {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch route visits", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);

            throw new \Exception("Something went wrong while fetching route visits, please try again.");
        }
    }


    public function generateCode(): string
    {
        do {
            $last = RouteVisit::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'RV' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (RouteVisit::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    // public function create(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data = array_merge($data, [
    //             'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //         ]);

    //         if (!isset($data['osa_code']) || empty($data['osa_code'])) {
    //             $data['osa_code'] = $this->generateCode();
    //         }

    //         foreach (['region', 'area', 'warehouse', 'route'] as $field) {
    //             if (isset($data[$field]) && is_array($data[$field])) {
    //                 $data[$field] = implode(',', $data[$field]);
    //             }
    //         }

    //         $routeVisit = RouteVisit::create($data);

    //         DB::commit();
    //         return $routeVisit;
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         $friendlyMessage = $e instanceof Error
    //             ? "Server error occurred."
    //             : "Something went wrong, please try again.";

    //         Log::error("RouteVisit creation failed", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'data' => $data,
    //             'user' => Auth::id(),
    //         ]);

    //         throw new \Exception($friendlyMessage, 0, $e);
    //     }
    // }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $createdRecords = [];

            if (isset($data['customers']) && is_array($data['customers'])) {
                $customerType = $data['customer_type'] ?? null;

                foreach ($data['customers'] as $customerData) {

                    // ✅ Attach merchandiser_id from root if not in customer
                    if (!isset($customerData['merchandiser_id']) && isset($data['merchandiser_id'])) {
                        $customerData['merchandiser_id'] = $data['merchandiser_id'];
                    }

                    // ✅ Check if customer already exists in RouteVisit
                    if (isset($customerData['customer_id'])) {
                        $existing = RouteVisit::where('customer_id', $customerData['customer_id'])->first();

                        if ($existing) {
                            throw new \Exception(
                                "Customer already exists."
                            );
                        }
                    }

                    $recordData = array_merge($customerData, [
                        'customer_type' => $customerType,
                        'uuid' => Str::uuid()->toString(),
                        'osa_code' => $this->generateCode(),
                    ]);

                    // Convert arrays to comma-separated strings
                    foreach (['region', 'area', 'warehouse', 'route', 'company_id'] as $field) {
                        if (isset($recordData[$field]) && is_array($recordData[$field])) {
                            $recordData[$field] = implode(',', $recordData[$field]);
                        }
                    }

                    // ✅ Manual load_date (optional)
                    if (isset($data['load_date'])) {
                        $recordData['load_date'] = $data['load_date'];
                    }

                    // ✅ Flag & Status setup
                    if ($customerType == 1) {
                        $recordData['flag'] = 'agent_customer';
                        $recordData['status'] = $recordData['status'] ?? 1;
                    } elseif ($customerType == 2) {
                        $recordData['flag'] = 'merchandisor';
                        $recordData['status'] = $recordData['status'] ?? 0;
                    } else {
                        $recordData['flag'] = 'unknown';
                    }

                    $createdRecords[] = RouteVisit::create($recordData);
                }
            } else {
                // ✅ Single record case
                if (isset($data['customer_id'])) {
                    $existing = RouteVisit::where('customer_id', $data['customer_id'])->first();

                    if ($existing) {
                        throw new \Exception(
                            "Customer already exists."
                        );
                    }
                }

                $data = array_merge($data, [
                    'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                    'osa_code' => $data['osa_code'] ?? $this->generateCode(),
                ]);

                foreach (['region', 'area', 'warehouse', 'route', 'company_id'] as $field) {
                    if (isset($data[$field]) && is_array($data[$field])) {
                        $data[$field] = implode(',', $data[$field]);
                    }
                }

                if (isset($data['load_date'])) {
                    $data['load_date'] = $data['load_date'];
                }

                if (isset($data['customer_type'])) {
                    if ($data['customer_type'] == 1) {
                        $data['flag'] = 'agent_customer';
                        $data['status'] = $data['status'] ?? 1;
                    } elseif ($data['customer_type'] == 2) {
                        $data['flag'] = 'merchandisor';
                        $data['status'] = $data['status'] ?? 0;
                    } else {
                        $data['flag'] = 'unknown';
                    }
                }

                $createdRecords[] = RouteVisit::create($data);
            }

            DB::commit();
            return $createdRecords;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("RouteVisit creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);

            throw new \Exception($e->getMessage());
        }
    }




    public function findByUuid(string $uuid): ?RouteVisit
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return RouteVisit::where('uuid', $uuid)->first();
    }

    public function updateByUuid(string $uuid, array $validated)
    {
        $routeVisit = $this->findByUuid($uuid);
        if (!$routeVisit) {
            throw new \Exception("Route Visit not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $data = array_merge($validated, []);

            foreach (['region', 'area', 'warehouse', 'route', 'company_id'] as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            $routeVisit->update($data);
            DB::commit();

            return $routeVisit->fresh();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("RouteVisit update failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'payload' => $validated,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function update(string $customer_id = null, array $data)
    {
        DB::beginTransaction();

        try {
            $updatedRecords = [];

            if (isset($data['customers']) && is_array($data['customers'])) {
                $customerType = $data['customer_type'] ?? null;

                foreach ($data['customers'] as $customerData) {
                    if (empty($customerData['customer_id'])) {
                        throw new \Exception("customer_id is required for each record in bulk update");
                    }

                    $routeVisit = RouteVisit::where('customer_id', $customerData['customer_id'])->first();

                    if (!$routeVisit) {
                        throw new \Exception("Route Visit not found for customer_id: {$customerData['customer_id']}");
                    }

                    $updateData = array_merge($customerData, [
                        'customer_type' => $customerType,
                    ]);

                    if (isset($updateData['days']) && is_array($updateData['days'])) {
                        $updateData['days'] = implode(',', $updateData['days']);
                    }

                    $updateData['flag'] = match ($customerType) {
                        1 => 'agent_customer',
                        2 => 'merchandisor',
                        default => 'unknown',
                    };
                    $routeVisit->fill($updateData);
                    $routeVisit->save();

                    $updatedRecords[] = $routeVisit->fresh();
                }
            } elseif ($customer_id) {
                $routeVisit = RouteVisit::where('customer_id', $customer_id)->first();

                if (!$routeVisit) {
                    throw new \Exception("Route Visit not found for customer_id: {$customer_id}");
                }

                if (isset($data['days']) && is_array($data['days'])) {
                    $data['days'] = implode(',', $data['days']);
                }

                $routeVisit->fill($data);
                $routeVisit->save();

                $updatedRecords[] = $routeVisit->fresh();
            }

            DB::commit();

            return $updatedRecords;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("RouteVisit bulk update failed", [
                'error' => $e->getMessage(),
                'data' => $data,
                'customer_id' => $customer_id,
            ]);

            throw new \Exception("Something went wrong while updating Route Visits.", 0, $e);
        }
    }



    public function deleteByUuid(string $uuid): void
    {
        $routeVisit = $this->findByUuid($uuid);
        if (!$routeVisit) {
            throw new \Exception("Route Visit not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $routeVisit->update([
                'deleted_at' => now(),
            ]);
            $routeVisit->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("RouteVisit delete failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function getAlll($filters = [])
    {
        $query = Salesman::select('id', 'osa_code', 'name')
            ->where('sub_type', 1); // ✅ Only subtype = 1

        // Optional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%']);
        }

        // Optional pagination
        if (!empty($filters['per_page'])) {
            return $query->paginate($filters['per_page']);
        }

        return $query->get();
    }

    public function getByMerchandiser($merchandiserId, $filters = [])
    {
        $query = DB::table('tbl_company_customer')
            ->select('id', 'osa_code', 'business_name as name')
            ->where('merchandiser_id', $merchandiserId);

        // Optional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('business_name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }
    public function export($format = 'csv')
    {
        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xls'])) {
            return [
                'status' => false,
                'message' => 'Invalid format. Use csv or xls only.',
            ];
        }

        // Fetch data with multi-ID handling
        $data = DB::table('route_visit')
            ->leftJoin('salesman', 'route_visit.merchandiser_id', '=', 'salesman.id')
            // ->leftJoin('tbl_route', function($join) {
            //     $join->whereRaw("tbl_route.id::text = ANY(string_to_array(route_visit.route::text, ','))");
            // })
            // ->leftJoin('tbl_region', function($join) {
            //     $join->whereRaw("tbl_region.id::text = ANY(string_to_array(route_visit.region::text, ','))");
            // })
            // ->leftJoin('tbl_areas', function($join) {
            //     $join->whereRaw("tbl_areas.id::text = ANY(string_to_array(route_visit.area::text, ','))");
            // })
            ->leftJoin('tbl_company', function ($join) {
                $join->whereRaw("tbl_company.id::text = ANY(string_to_array(route_visit.company_id::text, ','))");
            })
            ->leftJoin('tbl_company_customer', function ($join) {
                $join->whereRaw("tbl_company_customer.id::text = ANY(string_to_array(route_visit.customer_id::text, ','))");
            })
            ->leftJoin('agent_customers', function ($join) {
                $join->whereRaw("agent_customers.id::text = ANY(string_to_array(route_visit.customer_id::text, ','))");
            })
            ->select(
                'route_visit.id',
                'route_visit.customer_type',
                'route_visit.route',
                'route_visit.region',
                'route_visit.area',
                'route_visit.days',
                'route_visit.from_date',
                'route_visit.to_date',
                'salesman.name as merchandiser_name',
                // DB::raw("STRING_AGG(DISTINCT tbl_route.route_name, ', ') as route_names"),
                // DB::raw("STRING_AGG(DISTINCT tbl_region.region_name, ', ') as region_names"),
                // DB::raw("STRING_AGG(DISTINCT tbl_areas.area_name, ', ') as area_names"),
                DB::raw("STRING_AGG(DISTINCT tbl_company.company_name, ', ') as company_names"),
                DB::raw("COALESCE(
            STRING_AGG(DISTINCT tbl_company_customer.business_name, ', '),
            STRING_AGG(DISTINCT agent_customers.name, ', ')
        ) as customer_names")
            )
            ->groupBy('route_visit.id', 'salesman.name')
            ->orderBy('route_visit.id', 'desc')
            ->get();


        if ($data->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No data found in route_visit table.',
            ];
        }

        $directory = 'app/exports';
        Storage::makeDirectory($directory);

        $fileName = 'route_visit_' . now()->format('Ymd_His') . '.' . $format;
        $filePath = storage_path("{$directory}/{$fileName}");

        $arrayData = json_decode(json_encode($data), true);

        if ($format === 'csv') {
            $this->generateCsv($filePath, $arrayData);
        } else {
            $this->generateXls($filePath, $arrayData);
        }

        $fileUrl = str_replace('/public', '', url("storage/app/public/exports/{$fileName}"));

        return [
            'status' => true,
            'message' => 'Route Visit data exported successfully',
            'file_url' => $fileUrl,
            'file_path' => $filePath,
        ];
    }


    private function generateCsv($filePath, array $data): void
    {
        $handle = fopen($filePath, 'w');

        // Write header row
        fputcsv($handle, array_keys($data[0]));


        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
    private function generateXls($filePath, array $data): void
    {
        $handle = fopen($filePath, 'w');
        fwrite($handle, implode("\t", array_keys($data[0])) . "\n");
        foreach ($data as $row) {
            fwrite($handle, implode("\t", $row) . "\n");
        }

        fclose($handle);
    }

    public function globalSearch(?string $keyword, int $perPage = 50)
    {
        $query = RouteVisit::query();

        // If no keyword → return full paginated list
        if (empty($keyword)) {
            return $query->paginate($perPage);
        }

        $query->where(function ($q) use ($keyword) {

            $searchableFields = [
                'osa_code',
                'customer_type',
                'merchandiser_id',
                'status',
                'company_id',
                'region'
            ];

            foreach ($searchableFields as $field) {
                $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
            }

            $q->orWhereHas('agentCustomer', function ($sub) use ($keyword) {
                $sub->where('name', 'ILIKE', '%' . $keyword . '%');
            });

            $q->orWhereHas('companyCustomer', function ($sub) use ($keyword) {
                $sub->where('business_name', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('osa_code', 'ILIKE', '%' . $keyword . '%');
            });

            $q->orWhereHas('merchandiser', function ($sub) use ($keyword) {
                $sub->where('name', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('osa_code', 'ILIKE', '%' . $keyword . '%');
            });
        });

        return $query->paginate($perPage);
    }
}
