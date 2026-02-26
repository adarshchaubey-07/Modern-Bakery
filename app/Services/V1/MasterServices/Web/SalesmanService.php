<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Salesman;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Warehouse;
use App\Models\Route;
use App\Exports\SalesmanInvoicesExport;
use Illuminate\Support\Facades\Storage;
use App\Exports\SalesmanOrderExport;
use App\Exports\SalesmanPoExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Illuminate\Support\Facades\Hash;
use App\Exports\SalesmanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\DataAccessHelper;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderHeader;

class SalesmanService
{
    public function all(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = Salesman::with([
            'route:id,route_code,route_name',
            'salesmanType:id,salesman_type_code,salesman_type_name',
            'role:id,code,name',
            'channel:id,outlet_channel_code,outlet_channel',
            'company:id,company_code,company_name'
        ]);

        $query = DataAccessHelper::filterSalesmen($query, $user);

        $priorityStatus = array_key_exists('status', $filters)
            ? (int) $filters['status']
            : null;

        $query->select(
            $dropdown
                ? ['id', 'name', 'osa_code']
                : [
                    'id',
                    'uuid',
                    'osa_code',
                    'name',
                    'designation',
                    'contact_no',
                    'company_id',
                    'type',
                    'route_id',
                    'status',
                    'reason',
                    'is_block',
                    'forceful_login',
                    'role_id',
                    'channel_id',
                ]
        );

        foreach ($filters as $field => $value) {

            if ($field === 'status') {
                continue; 
            }

            if ($value !== null && $value !== '') {
                if ($field === 'salesman_id') {
                    $ids = is_string($value) && str_contains($value, ',')
                        ? array_map('intval', explode(',', $value))
                        : (array) $value;

                    $query->whereIn('id', array_filter($ids));
                    continue;
                }
                if ($field === 'route_id') {
                $routeIds = is_string($value) && str_contains($value, ',')
                    ? array_map('intval', explode(',', $value))
                    : (array) $value;

                $query->whereIn('route_id', array_filter($routeIds));
                continue;
            }

                if (in_array($field, ['osa_code', 'name', 'designation', 'username'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }
            if (!empty($filters['type'])) {
                $query->whereIn(
                    'type',
                    is_array($filters['type'])
                        ? $filters['type']
                        : [$filters['type']]
                );
            }

            if (!empty($filters['salesman_type_name'])) {
                $query->whereHas('salesmanType', function ($q) use ($filters) {
                    $q->whereRaw(
                        'LOWER(salesman_type_name) LIKE ?',
                        ['%' . strtolower($filters['salesman_type_name']) . '%']
                    );
                });
            }
            if (!empty($filters['role_id'])) {
                $query->whereIn(
                    'role_id',
                    is_array($filters['role_id'])
                        ? $filters['role_id']
                        : [$filters['role_id']]
                );
            }

            if (!empty($filters['role_name'])) {
                $query->whereHas('role', function ($q) use ($filters) {
                    $q->whereRaw(
                        'LOWER(name) LIKE ?',
                        ['%' . strtolower($filters['role_name']) . '%']
                    );
                });
            }
            if (!empty($filters['channel_id'])) {
                $query->whereIn(
                    'channel_id',
                    is_array($filters['channel_id'])
                        ? $filters['channel_id']
                        : [$filters['channel_id']]
                );
            }

            if (!empty($filters['channel_name'])) {
                $query->whereHas('channel', function ($q) use ($filters) {
                    $q->whereRaw(
                        'LOWER(outlet_channel) LIKE ?',
                        ['%' . strtolower($filters['channel_name']) . '%']
                    );
                });
            }
            if (!empty($filters['route_name'])) {
                $query->whereHas('route', function ($q) use ($filters) {
                    $q->whereRaw(
                        'LOWER(route_name) LIKE ?',
                        ['%' . strtolower($filters['route_name']) . '%']
                    );
                });
            }

            if (!empty($filters['route_code'])) {
                $query->whereHas('route', function ($q) use ($filters) {
                    $q->whereRaw(
                        'LOWER(route_code) LIKE ?',
                        ['%' . strtolower($filters['route_code']) . '%']
                    );
                });
            }
        }

        if ($priorityStatus !== null) {
            $query->orderByRaw(
                "CASE 
                WHEN status = {$priorityStatus} THEN 0 
                ELSE 1 
             END"
            );
        }

        $query->orderBy('id', 'DESC');

        return $dropdown
            ? $query->get()
            : $query->paginate($perPage);
    }
    public function findByUuid(string $uuid)
    {
        $salesman = Salesman::with([
            'route:id,route_code,route_name',
            'salesmanType:id,salesman_type_code,salesman_type_name',
            'company:id,company_code,company_name'
        ])
            ->where('uuid', $uuid)
            ->first();
        if (!$salesman) {
            throw new ModelNotFoundException("Salesman not found with UUID: {$uuid}");
        }
        return $salesman;
    }
    public function generateCode(): string
    {
        do {
            $last = Salesman::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'SA' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Salesman::where('osa_code', $osa_code)->exists());
        return $osa_code;
    }
    public function create(array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'password' => Hash::make($data['password']),
                'created_user' => Auth::user()->id,
            ]);

            if (empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }
            $salesman = Salesman::create($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
            Log::error('Salesman creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);
            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function updateByUuid(string $uuid, array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            $salesman->update($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Salesman update failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $data,
            ]);
            throw new \Exception("Something went wrong while updating.", 0, $e);
        }
    }
    public function deleteByUuid(string $uuid): bool
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            $salesman->delete();

            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error('Salesman delete failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
    public function export(string $format = 'xlsx', ?string $fromDate = null, ?string $toDate = null, ?string $search = null, array $filters = [], array $columns = []): array
    {
        try {
            $allowedFormats = ['xlsx', 'csv'];
            if (!in_array($format, $allowedFormats)) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

            $extension = $format === 'csv' ? 'csv' : 'xlsx';
            $fileName  = 'salesmen_export_' . now()->format('Ymd_His') . '.' . $extension;
            $path      = 'salesmenexports/' . $fileName;

            $export = new SalesmanExport($fromDate, $toDate, $search, $filters, $columns);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', ExcelExcel::CSV);
            } else {
                Excel::store($export, $path, 'public', ExcelExcel::XLSX);
            }

            $appUrl = rtrim(config('app.url'), '/');
            $downloadUrl = $appUrl . '/storage/app/public/' . $path;

            return [
                'status'       => 'success',
                'code'         => 200,
                'message'      => 'Salesmen export generated successfully',
                'download_url' => $downloadUrl
            ];
        } catch (\Throwable $e) {

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to generate salesmen export',
                'error'   => $e->getMessage()
            ];
        }
    }
    public function updateSalesmenStatus(array $salesmanIds, $status)
    {
        $updated = Salesman::whereIn('id', $salesmanIds)->update(['status' => $status]);
        return $updated > 0;
    }
public function globalSearch(int $perPage = 50, ?string $keyword = null)
{
    try {
        $user = auth()->user();

        $query = Salesman::query()
            ->select('salesman.*')
            ->leftJoin('tbl_route', 'tbl_route.id', '=', 'salesman.route_id')
            ->leftJoin('salesman_types', 'salesman_types.id', '=', 'salesman.type')
            ->leftJoin('salesman_roles', 'salesman_roles.id', '=', 'salesman.role_id')
            ->leftJoin('tbl_company', 'tbl_company.id', '=', 'salesman.company_id')
            ->leftJoin('outlet_channel', 'outlet_channel.id', '=', 'salesman.channel_id');

        if (!empty($keyword)) {

            $query->where(function ($q) use ($keyword) {

                $keyword = "%{$keyword}%";

                $q->orWhere('salesman.name', 'ILIKE', $keyword)
                  ->orWhere('salesman.osa_code', 'ILIKE', $keyword)
                  ->orWhere('salesman.contact_no', 'ILIKE', $keyword)
                  ->orWhere('salesman.email', 'ILIKE', $keyword)
                  ->orWhere('salesman.designation', 'ILIKE', $keyword)
                  ->orWhere('salesman.status', 'ILIKE', $keyword)

                  ->orWhere('tbl_route.route_name', 'ILIKE', $keyword)
                  ->orWhere('tbl_route.route_code', 'ILIKE', $keyword)

                  ->orWhere('salesman_types.salesman_type_code', 'ILIKE', $keyword)
                  ->orWhere('salesman_types.salesman_type_name', 'ILIKE', $keyword)

                  ->orWhere('salesman_roles.code', 'ILIKE', $keyword)
                  ->orWhere('salesman_roles.name', 'ILIKE', $keyword)

                  ->orWhere('tbl_company.company_code', 'ILIKE', $keyword)
                  ->orWhere('tbl_company.company_name', 'ILIKE', $keyword)

                  ->orWhere('outlet_channel.outlet_channel_code', 'ILIKE', $keyword)
                  ->orWhere('outlet_channel.outlet_channel', 'ILIKE', $keyword);
            });
        }

        $query = DataAccessHelper::filterSalesmen($query, $user);

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        throw new \Exception("Failed to search salesman: " . $e->getMessage());
    }
}
    public function salespersalesman(string $uuid, int $perPage = 50, bool $dropdown = false, ?string $from = null, ?string $to = null)
    {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');
            if (!$salesmanId) {
                throw new \Exception("Salesman not found for given UUID.");
            }
            $query = InvoiceHeader::with([
                'customer:id,osa_code,name',
                'route:id,route_code,route_name'
            ])
                ->where('salesman_id', $salesmanId)
                ->latest();
            if ($from && $to) {
                $query->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($from)),
                    date('Y-m-d 23:59:59', strtotime($to))
                ]);
            } elseif ($from) {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($from)));
            } elseif ($to) {
                $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($to)));
            }
            if ($dropdown) {
                return $query->select(['id', 'invoice_number'])->get();
            }
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch sales for salesman: " . $e->getMessage());
        }
    }

    public function exportInvoicesBySalesman(string $uuid, string $format = 'csv')
    {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');
            if (!$salesmanId) {
                throw new Exception("Salesman not found for the given UUID.");
            }
            $timestamp = now()->format('Ymd_His');
            $fileName = "salesman_invoices_{$timestamp}.{$format}";
            $filePath = "exports/{$fileName}";
            $writerType = $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV;
            $success = \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\SalesmanInvoicesExport($salesmanId),
                $filePath,
                'public',
                $writerType
            );
            if (!$success) {
                throw new Exception(strtoupper($format) . ' export failed.');
            }
            $appUrl = rtrim(config('app.url'), '/');
            $downloadUrl = "{$appUrl}/storage/app/public/{$filePath}";
            return [
                'download_url' => $downloadUrl,
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to export invoices: " . $e->getMessage());
        }
    }
    public function salesmanOrder(
        string $uuid,
        int $perPage = 50,
        bool $dropdown = false,
        ?string $from = null,
        ?string $to = null
    ) {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');
            if (!$salesmanId) {
                throw new \Exception("Salesman not found for the given UUID.");
            }
            $query = \App\Models\Agent_Transaction\OrderHeader::with([
                'customer:id,osa_code,name',
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'details:id,header_id,item_id,quantity,item_price,total',
            ])
                ->where('salesman_id', $salesmanId)
                ->latest();
            if ($from && $to) {
                $query->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($from)),
                    date('Y-m-d 23:59:59', strtotime($to)),
                ]);
            } elseif ($from) {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($from)));
            } elseif ($to) {
                $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($to)));
            }
            if ($dropdown) {
                return $query->select(['id', 'order_number'])->get();
            }
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch orders for salesman: " . $e->getMessage());
        }
    }
    public function exportBySalesmanUuid(string $uuid): string
    {
        $salesman = Salesman::where('uuid', $uuid)->firstOrFail();
        $headers = OrderHeader::with([
            'customer:id,name',
            'route:id,route_name',
            'salesman:id,name',
            'country:id,country_name',
            'details.item:id,name',
            'details.uoms:id,name',
            'details.discounts:id,discount_name',
            // 'details.promotion:id,promotion_name',
            // 'details.parent:id,name',
        ])
            ->where('salesman_id', $salesman->id)
            ->get();
        $fileName = 'salesman_orders_' . Str::random(8) . '.xlsx';
        $folder   = 'salesman_order';
        Excel::store(
            new SalesmanOrderExport($headers),
            "{$folder}/{$fileName}",
            'public'
        );
        return asset("storage/{$folder}/{$fileName}");
    }

    public function export_po(string $uuid): string
    {
        $salesman = Salesman::where('uuid', $uuid)->firstOrFail();
        $headers = PoOrderHeader::with([
            'customer:id,business_name',
            'salesman:id,name',
            'company:id,company_name',
            'details.item:id,name',
            'details.uom:id,name',
            // 'details.discounts:id,discount_name',
            // 'details.promotion:id,promotion_name',
            // 'details.parent:id,name',
        ])
            ->where('salesman_id', $salesman->id)
            ->get();
        $fileName = 'salesman_Po_' . Str::random(8) . '.xlsx';
        $folder   = 'salesman_Po';
        Excel::store(
            new SalesmanPoExport($headers),
            "{$folder}/{$fileName}",
            'public'
        );
        return asset("storage/{$folder}/{$fileName}");
    }
}
