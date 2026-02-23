<?php

namespace App\Services\V1\Assets\Web;

use App\Models\IRHeader;
use App\Models\IROHeader;
use App\Models\Salesman;
use App\Models\IRDetail;
use App\Models\AddChiller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IRService
{
    /**
     * Get list of IR headers with filters and pagination
     */
    public function list(int $perPage = 10, array $filters = [])
    {
        try {
            $query = IRHeader::with('details');

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code', 'iro_id', 'status'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("Failed to fetch IR Headers", [
                'error'   => $e->getMessage(),
                'filters' => $filters,
            ]);

            throw new \Exception("Unable to fetch IR Headers at this time.");
        }
    }


    /**
     * Generate IR code like IRO001
     */
    public function generateCode(): string
    {
        do {
            $last = IRHeader::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'IR' . str_pad($next, 5, '0', STR_PAD_LEFT);
        } while (IRHeader::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }


    /**
     * Create Header + Multiple Details in Transaction
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            /**
             * STEP 1: Create IR Header
             */
            $data = array_merge($data, [
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $data['osa_code'] ?? $this->generateCode(),
            ]);
            // dd($data);
            $header = IRHeader::create([
                'uuid'         => $data['uuid'],
                'osa_code'     => $data['osa_code'],
                'iro_id'        => $data['iro_id'],
                'salesman_id'   => $data['salesman_id'],
                'schedule_date' => $data['schedule_date'] ?? null,
                'status'        => $data['status'] ?? 1,
            ]);

            /**
             * STEP 2: Insert details + update chiller table
             */
            foreach ($data['details'] as $detail) {

                // Insert detail record
                $irDetail = IRDetail::create([
                    'uuid'         => $data['uuid'],
                    'header_id'    => $header->id,
                    'fridge_id'    => $detail['fridge_id'],
                    'agreement_id' => $detail['agreement_id'] ?? null,
                    'crf_id'       => $detail['crf_id'] ?? null,
                ]);

                /**
                 * UPDATE: tbl_add_chillers
                 * is_assign = 2 (waiting for installation)
                 * status    = 4 (assigned)
                 * warehouse_id = provided depot/warehouse in payload
                 */
                AddChiller::where('id', $detail['fridge_id'])
                    ->update([
                        'is_assign'    => 2,
                        'status'       => 4,
                        'warehouse_id' => $data['warehouse_id']
                    ]);
            }

            DB::commit();

            return $header->load('details');
        } catch (Throwable $e) {

            dd($e);
            DB::rollBack();

            Log::error("Failed to create IR", [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);

            throw new \Exception("Failed to create IR. Please try again.");
        }
    }



    /**
     * Show by ID with details
     */
    public function show($id)
    {
        try {
            return IRHeader::with('details')->findOrFail($id);
        } catch (Throwable $e) {
            throw new \Exception("IR not found for ID: {$id}");
        }
    }


    /**
     * Search IR by code
     */
    public function globalSearch(int $perPage = 10, string $searchTerm = null)
    {
        try {
            $query = IRHeader::query()->with('details');

            if (!empty($searchTerm)) {
                $search = strtolower($searchTerm);
                $query->whereRaw("LOWER(ir_code) LIKE ?", ["%{$search}%"]);
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("IR global search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search IR headers.");
        }
    }

    public function getAllIRO()
    {
        try {
            return IROHeader::withCount('details')
                ->with([
                    'details' => function ($q) {
                        $q->select('id', 'header_id', 'warehouse_id');
                    },
                    'details.warehouse' => function ($q) {
                        $q->select('id', 'warehouse_name', 'warehouse_code');
                    }
                ])
                ->where('status', 1)
                ->orderByDesc('id')
                ->get()
                ->map(function ($row) {

                    /** 
                     * Get FIRST detail warehouse (if many details exist),
                     * otherwise null.
                     */
                    $warehouse = optional($row->details->first()->warehouse ?? null);

                    return [
                        'id'  => $row->id,
                        'code'  => $row->osa_code,
                        'count' => $row->details_count,
                        'warehouse' => [
                            'id' => $warehouse->id ?? null,
                            'name' => $warehouse->warehouse_name ?? null,
                            'code' => $warehouse->warehouse_code ?? null,
                        ]
                    ];
                });
        } catch (Throwable $e) {

            Log::error("Failed to fetch IR headers", [
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Unable to fetch IR header data.");
        }
    }




    public function getAllSalesman()
    {
        try {
            return Salesman::where('sub_type', 11)
                ->orderByDesc('id')
                ->get(['id', 'name', 'osa_code']);
        } catch (Throwable $e) {
            Log::error("Failed to fetch salesman", [
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Unable to fetch salesman data.");
        }
    }

    public function header(int $page = 50, array $filters = [], array $status = [])
    {
        try {

            $query = IRHeader::query()
                ->select([
                    'tbl_ir_headers.id',
                    'tbl_ir_headers.uuid',
                    'tbl_ir_headers.osa_code',
                    'tbl_ir_headers.iro_id',
                    'tbl_ir_headers.salesman_id',
                    'tbl_ir_headers.schedule_date',
                    'tbl_ir_headers.status',
                    'tbl_ir_headers.created_user',
                    'tbl_ir_headers.created_at',

                    // IRO Header
                    'tbl_iro_headers.osa_code as iro_code',

                    // Warehouse
                    'tbl_warehouse.warehouse_code',
                    'tbl_warehouse.warehouse_name',

                    // Salesman
                    'salesman.osa_code as salesman_code',
                    'salesman.name as salesman_name',
                ])
                ->leftJoin('tbl_iro_headers', 'tbl_iro_headers.id', '=', 'tbl_ir_headers.iro_id')
                ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'tbl_iro_headers.warehouse_id')
                ->leftJoin('salesman', 'salesman.id', '=', 'tbl_ir_headers.salesman_id');

            // ➤ COUNT details
            $query->withCount([
                'details as count' => function ($q) {
                    $q->whereColumn('tbl_ir_details.header_id', 'tbl_ir_headers.id');
                }
            ]);

            // ➤ STATUS filtering
            if (!empty($status)) {
                $query->whereIn('tbl_ir_headers.status', $status);
            } else {
                $query->whereNotIn('tbl_ir_headers.status', [5]);
            }

            // ➤ DYNAMIC FILTERS
            foreach ($filters as $field => $value) {
                if (empty($value)) continue;

                if (in_array($field, ['osa_code', 'iro_id', 'status'])) {
                    $query->whereRaw("LOWER(tbl_ir_headers.{$field}) LIKE ?", [
                        '%' . strtolower($value) . '%'
                    ]);
                } else {
                    $query->where("tbl_ir_headers.{$field}", $value);
                }
            }

            // ➤ FINAL ORDER & PAGINATION
            return $query->orderBy('tbl_ir_headers.id', 'desc')->paginate($page);
        } catch (Throwable $e) {
// dd($e);
            Log::error("Failed to fetch IR Headers", [
                'error'   => $e->getMessage(),
                'filters' => $filters,
                'status'  => $status
            ]);

            throw new \Exception("Unable to fetch IR Headers at this time.");
        }
    }
}
