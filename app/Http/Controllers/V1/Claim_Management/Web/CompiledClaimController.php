<?php

namespace App\Http\Controllers\V1\Claim_Management\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Claim_Management\Web\CompiledClaimRequest;
use App\Http\Resources\V1\Claim_Management\Web\CompiledClaimResource;
use App\Services\V1\Claim_Management\Web\CompiledClaimService;
use Illuminate\Http\Request;
use App\Exports\CompiledClaimExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Claim_Management\Web\CompiledClaim;

class CompiledClaimController extends Controller
{
    protected $service;

    public function __construct(CompiledClaimService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->getAll(
            $request->per_page ?? 50,
            $request->all()
        );

        return response()->json([
            "status" => "success",
            "message" => "Compiled Claim Listing",
            "data" => CompiledClaimResource::collection($data),
            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }

    public function store(CompiledClaimRequest $request)
    {
        try {
            $claim = $this->service->create($request->validated());

            return response()->json([
                "status" => "success",
                "message" => "Claim Created Successfully",
                "data" => new CompiledClaimResource($claim)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to create claim",
                "error" => $e->getMessage(),
            ], 400);
        }
    }

    // public function export()
    // {
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));

    //     $filename = 'compiled_claims_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     $query = \DB::table('tbl_compiled_claim')
    //         ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'tbl_compiled_claim.warehouse_id')
    //         ->select(
    //             'tbl_compiled_claim.*',
    //             'tbl_warehouse.warehouse_code',
    //             'tbl_warehouse.warehouse_name'
    //         );

    //     // Filters
    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('tbl_compiled_claim.warehouse_id', $filters['warehouse_id']);
    //     }

    //     if (!empty($filters['claim_period'])) {
    //         $query->where('tbl_compiled_claim.claim_period', $filters['claim_period']);
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('tbl_compiled_claim.status', $filters['status']);
    //     }

    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('tbl_compiled_claim.created_at', '>=', $filters['from_date']);
    //     }

    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('tbl_compiled_claim.created_at', '<=', $filters['to_date']);
    //     }

    //     $data = $query->get();

    //     if ($data->isEmpty()) {
    //         return response()->json(['message' => 'No data available for export'], 404);
    //     }

    //     // Create export instance
    //     $export = new CompiledClaimExport($data);

    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    //     $success = Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );

    //     if (!$success) {
    //         throw new \Exception(strtoupper($format) . ' export failed.');
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

    //     return response()->json(['url' => $fullUrl], 200);
    // }


    public function export()
    {
        $filters = request()->input('filters', []);
        $format = strtolower(request()->input('format', 'csv'));

        $filename = 'compiled_claims_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        // Use MODEL instead of raw query
        $query = CompiledClaim::with('warehouse');  // relation used

        // Filters
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['claim_period'])) {
            $query->where('claim_period', $filters['claim_period']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data available for export'], 404);
        }

        $export = new CompiledClaimExport($data);
        // dd($export);
        $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

        $success = Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        if (!$success) {
            throw new \Exception(strtoupper($format) . ' export failed.');
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

        return response()->json(['url' => $fullUrl], 200);
    }
}
