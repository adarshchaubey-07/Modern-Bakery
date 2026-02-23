<?php

namespace App\Services\V1\Assets\Web;

use App\Models\IRODetail;

use App\Models\ChillerRequest;
use App\Models\AddChiller;
use App\Models\IROHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class IROHeaderService
{
    public function getAll(int $perPage = 10, array $filters = [])
    {
        try {
            $query = IROHeader::query();

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code', 'name', 'status'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch InstallationOrderHeaders", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);

            throw new \Exception("Unable to fetch Installation Order Headers at this time. Please try again later.");
        }
    }


    public function getDetailCountWithHeader(array $filters = [])
    {
        try {
            $query = IRODetail::query();

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    $query->where($field, $value);
                }
            }

            $count = $query->count();

            $headerIds = $query->pluck('header_id')->unique();

            $headers = IROHeader::select([
                'id',
                'uuid',
                'osa_code',
                'status',
                'updated_user',
                'created_at'
            ])
                ->with([
                    'updatedBy:id,name',
                    'details' => function ($q) {
                        $q->with([
                            'warehouse:id,warehouse_code,warehouse_name',
                            'chillerRequest'
                        ]);
                    }
                ])
                ->whereIn('id', $headerIds)
                ->get();


            return [
                'count'   => $count,
                'headers' => $headers
            ];
        } catch (Throwable $e) {
            throw new \Exception("Unable to fetch detail count. " . $e->getMessage());
        }
    }


    public function generateOsaCode(): string
    {
        try {
            do {
                $last = IROHeader::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
                $osa_code = 'IRO' . str_pad($next, 3, '0', STR_PAD_LEFT);
            } while (IROHeader::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            Log::error("Failed to generate OSA code", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Unable to generate unique OSA code. Please try again.");
        }
    }

    public function generateOsaCodeDetail(): string
    {
        try {
            do {
                $last = IRODetail::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
                $osa_code = 'IRO-D' . str_pad($next, 3, '0', STR_PAD_LEFT);
            } while (IRODetail::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            Log::error("Failed to generate OSA code", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Unable to generate unique OSA code. Please try again.");
        }
    }

    public function store(array $data): IROHeader
    {
        DB::beginTransaction();

        try {
            $data['uuid']     = $data['uuid'] ?? Str::uuid()->toString();
            $data['osa_code'] = $data['osa_code'] ?? $this->generateOsaCode();

            $header = IROHeader::create([
                'uuid'     => $data['uuid'],
                'osa_code' => $data['osa_code'],
                'status'   => $data['status'] ?? 1,
            ]);

            if (empty($data['crf_id'])) {
                throw new \Exception("crf_id is required.");
            }

            // ✅ Normalize CRF IDs
            $crfIds = is_array($data['crf_id'])
                ? $data['crf_id']
                : explode(',', $data['crf_id']);

            $crfIds = array_filter(array_map('intval', $crfIds));

            $chillerRequests = ChillerRequest::select('id', 'warehouse_id')
                ->whereIn('id', $crfIds)
                ->get();

            if ($chillerRequests->isEmpty()) {
                throw new \Exception("Invalid crf_id(s)");
            }

            foreach ($chillerRequests as $crf) {
                $header->details()->create([
                    'uuid'         => Str::uuid()->toString(),
                    'osa_code'     => $this->generateOsaCodeDetail(),
                    'crf_id'       => $crf->id,
                    'warehouse_id' => $crf->warehouse_id,
                ]);
            }

            ChillerRequest::whereIn('id', $crfIds)
                ->update(['status' => 5]);

            DB::commit();
            return $header;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }




    public function findByCrfId($id)
    {
        if (!$id) {
            throw new \Exception("Invalid CRF ID provided: {$id}");
        }

        // Fetch ALL records matching crf_id
        $records = IROHeader::where('id', $id)->get();

        if ($records->isEmpty()) {
            throw new \Exception("No Installation Order Headers found for CRF ID: {$id}");
        }

        return $records;
    }


    public function update(string $uuid, array $data): IROHeader
    {
        $record = $this->findByUuid($uuid);

        DB::beginTransaction();

        try {
            $record->fill($data);
            $record->save();

            DB::commit();
            return $record;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Failed to update InstallationOrderHeader", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Failed to update Installation Order Header with UUID: {$uuid}. Please try again.");
        }
    }

    /**
     * Delete record by UUID (soft delete)
     */
    // public function delete(string $uuid): void
    // {
    //     $record = $this->findByUuid($uuid);

    //     DB::beginTransaction();

    //     try {
    //         $record->save();
    //         $record->delete();

    //         DB::commit();
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error("Failed to delete InstallationOrderHeader", [
    //             'error' => $e->getMessage(),
    //             'uuid'  => $uuid,
    //         ]);

    //         throw new \Exception("Failed to delete Installation Order Header with UUID: {$uuid}. Please try again.");
    //     }
    // }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = IROHeader::with([
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch, $searchTerm) {
                    $q->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("Installation Order Header search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search Installation Order Header: " . $e->getMessage(), 0, $e);
        }
    }

    public function getChillers(int $headerId, int $warehouseId)
    {
        try {

            // STEP 1: Get CRF IDs from details
            $crfIds = IRODetail::where('header_id', $headerId)
                ->pluck('crf_id')
                ->filter()
                ->toArray();

            if (empty($crfIds)) {
                return [];
            }

            // STEP 2: Models requested
            $requestedModels = ChillerRequest::whereIn('id', $crfIds)
                ->pluck('model_number')
                ->filter()
                ->unique()
                ->toArray();

            if (empty($requestedModels)) {
                return [];
            }

            // STEP 3: Depot Stock (status = 5)
            $modelStock = AddChiller::select(
                'id',
                'serial_number',
                'model_number',
                'status',
                'is_assign',
                'assets_category',
                'model_number',
                'branding',
            )
                ->where('is_assign', 0)
                ->where('status', 5)
                ->whereIn('model_number', $requestedModels)
                ->where('warehouse_id', $warehouseId)
                ->with([
                    'assetsCategory:id,name,osa_code',
                    'modelNumber:id,name,code',
                    'brand:id,name,osa_code',
                ])
                ->orderByDesc('id')
                ->get();


            // STEP 4: Warehouse Stock (status = 3)
            $warehouseStock = AddChiller::select(
                'id',
                'serial_number',
                'model_number',
                'status',
                'is_assign',
                'assets_category',
                'model_number',
                'branding',
            )
                ->where('is_assign', 0)
                ->where('status', 3)
                ->whereIn('model_number', $requestedModels)
                ->with([
                    'assetsCategory:id,name,osa_code',
                    'modelNumber:id,name,code',
                    'brand:id,name,osa_code',
                ])
                ->orderByDesc('id')
                ->get();

            // STEP 5: Merge results
            return $modelStock->isNotEmpty()
                ? $warehouseStock->merge($modelStock)
                : $warehouseStock;
        } catch (Throwable $e) {
            Log::error("Failed to fetch chiller list", [
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Unable to fetch chiller data.");
        }
    }
}
