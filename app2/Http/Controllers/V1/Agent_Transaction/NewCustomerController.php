<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\NewCustomerRequest;
use App\Http\Resources\V1\Agent_Transaction\NewCustomerResource;
use App\Services\V1\Agent_Transaction\NewCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exports\NewCustomerFullExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Agent_Transaction\NewCustomer;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @OA\Schema(
 *     schema="NewCustomer",
 *     type="object",
 *     required={"name", "owner_name", "route_id"},
 *     @OA\Property(property="name", type="string", example="AgentCustomer"),
 *     @OA\Property(property="business_name", type="string", example="My Business"),
 *     @OA\Property(property="customer_type", type="integer", example=0, description="0=Retail, 1=Wholesale"),
 *     @OA\Property(property="route_id", type="integer", example=1),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_no", type="string", example="9876543210"),
 *     @OA\Property(property="is_whatsapp", type="integer", example=1, description="1=Yes, 0=No"),
 *     @OA\Property(property="whatsapp_no", type="string", example="9876543210"),
 *     @OA\Property(property="email", type="string", example="customer@example.com"),
 *     @OA\Property(property="language", type="string", example="English"),
 *     @OA\Property(property="contact_no2", type="string", example="0123456789"),
 *     @OA\Property(property="buyertype", type="integer", example=0, description="0=Regular, 1=Occasional"),
 *     @OA\Property(property="payment_type", type="integer", example=0, description="0=Cash, 1=Credit"),
 *     @OA\Property(property="creditday", type="integer", example=30),
 *     @OA\Property(property="tin_no", type="string", example="TIN123456"),
 *     @OA\Property(property="threshold_radius", type="integer", example=100),
 *     @OA\Property(property="outlet_channel_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=1),
 *     @OA\Property(property="region_id", type="integer", example=1),
 *     @OA\Property(property="area_id", type="integer", example=1),
 *     @OA\Property(property="latitude", type="number", format="float", example=28.6139391),
 *     @OA\Property(property="longitude", type="number", format="float", example=77.2090212),
 *     @OA\Property(property="approval_status", type="integer", example=2, description="1=Approved, 2=Pending, 3=Rejected"),
 *     @OA\Property(property="reject_reason", type="string", example="Incomplete KYC details"),
 *     @OA\Property(property="status", type="integer", example=1, description="1=Active, 0=Inactive"),
 * )
 */
class NewCustomerController extends Controller
{
    protected NewCustomerService $service;

    public function __construct(NewCustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/new-customer/list",
     *     tags={"NewCustomer"},
     *     summary="Get all new customers with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="approval_status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of new customers")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['approval_status', 'search']);
            $perPage = $request->get('limit', 10);
            $customers = $this->service->getAll($perPage, $filters);
            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'message'    => 'Customers fetched successfully',
                'data'       => NewCustomerResource::collection($customers->items()),
                'pagination' => [
                    'page'         => $customers->currentPage(),
                    'limit'        => $customers->perPage(),
                    'totalPages'   => $customers->lastPage(),
                    'totalRecords' => $customers->total(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/new-customer/{uuid}",
     *     tags={"NewCustomer"},
     *     summary="Get new customer details by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer details fetched successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $customer = $this->service->findByUuid($uuid);

            if (!$customer) {
                return response()->json([
                    'status'  => 'fail',
                    'code'    => 404,
                    'message' => 'Customer not found',
                    'data'    => null
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Customer fetched successfully',
                'data'    => new NewCustomerResource($customer)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/agent_transaction/new-customer/add",
    //  *     tags={"NewCustomer"},
    //  *     summary="Create a new customer entry",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/NewCustomer")),
    //  *     @OA\Response(response=201, description="Customer created successfully"),
    //  *     @OA\Response(response=422, description="Validation failed")
    //  * )
    //  */
    // public function store(NewCustomerRequest $request): JsonResponse
    // {
    //     try {
    //         $customer = $this->service->create($request->validated());

    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 201,
    //             'message' => 'Customer created successfully',
    //             'data'    => new NewCustomerResource($customer)
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }

    //     /**
    //      * @OA\Post(
    //      *     path="/api/agent_transaction/new-customer/add",
    //      *     tags={"NewCustomer"},
    //      *     summary="Create or update a customer entry based on flag and approval status",
    //      *     security={{"bearerAuth":{}}},
    //      *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/NewCustomer")),
    //      *     @OA\Response(response=201, description="Customer created or updated successfully"),
    //      *     @OA\Response(response=422, description="Validation failed")
    //      * )
    //      */
    //    public function store(NewCustomerRequest $request): JsonResponse
    // {
    //     try {
    //         // ✅ Validate and get data
    //         $data = $request->validated();

    //         // ✅ Call the service function (handles flag logic)
    //         $result = $this->service->updateCustomer($data);

    //         // ✅ Return success response
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => $result['message'],
    //             'data'    => new NewCustomerResource($result['customer'])
    //         ], 200);

    //     } catch (Throwable $e) {
    //         // ✅ Log for debugging
    //         Log::error('New Customer creation/update failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         // ❌ Return error response
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => $e->getMessage() ?? 'Something went wrong. Please try again later.'
    //         ], 500);
    //     }
    // }


    /**
     * @OA\Post(
     *     path="/api/agent_transaction/new-customer/add",
     *     tags={"NewCustomer"},
     *     summary="Create, update, or reject customer based on approval_status flag",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="approval_status", type="integer", example=1, description="1 = New, 2 = Update, 3 = Reject"),
     *             @OA\Property(property="customer_id", type="integer", example=94, description="Existing AgentCustomer ID to check"),
     *             @OA\Property(property="name", type="string", example="Updated AgentCustomer"),
     *             @OA\Property(property="business_name", type="string", example="Updated Business"),
     *             @OA\Property(property="customer_type", type="integer", example=20),
     *             @OA\Property(property="warehouse", type="integer", example=121),
     *             @OA\Property(property="route_id", type="integer", example=64),
     *             @OA\Property(property="owner_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="contact_no", type="string", example="9998887777"),
     *             @OA\Property(property="email", type="string", example="updatedcustomer@example.com"),
     *             @OA\Property(property="outlet_channel_id", type="integer", example=1),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="subcategory_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Success"),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=500, description="Server Error")
     * )
     */
    public function store(NewCustomerRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            // dd($data);
            $result = $this->service->updateCustomer($data);
            // dd($result);
            return response()->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => $result['message'],
                'data'    => new NewCustomerResource($result['customer'])
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Put(
     *     path="/api/agent_transaction/new-customer/update/{uuid}",
     *     tags={"NewCustomer"},
     *     summary="Update new customer details by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/NewCustomer")),
     *     @OA\Response(response=200, description="Customer updated successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function update(NewCustomerRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->updateByUuid($uuid, $request->validated());

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Customer updated successfully',
                'data'    => new NewCustomerResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/new-customer/{uuid}",
     *     tags={"NewCustomer"},
     *     summary="Soft delete new customer by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer deleted successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Customer deleted successfully',
                'data'    => null
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/new-customer/generate-code",
     *     tags={"NewCustomer"},
     *     summary="Generate unique OSA code for new customers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Unique OSA code generated successfully")
     * )
     */
    public function generateCode(): JsonResponse
    {
        $code = $this->service->generateCode(); // service generates automatically

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Unique customer code generated successfully',
            'data'    => ['osa_code' => $code]
        ]);
    }

    // public function generateCode(): JsonResponse
    // {
    //     try {
    //         $osaCode = $this->service->generateCode1();

    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 200,
    //             'message' => 'OSA code generated successfully',
    //             'data' => ['osa_code' => $osaCode]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 500,
    //             'message' => 'Failed to generate OSA code: ' . $e->getMessage(),
    //             'data' => null
    //         ]);
    //     }
    // }

    // public function generateCode(): JsonResponse
    // {
    //     try {
    //         $code = $this->service->generateCode();

    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Unique OSA code generated successfully',
    //             'data'    => ['osa_code' => $code]
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/new-customer/export",
     *     tags={"NewCustomer"},
     *     summary="Export all new customer records to CSV",
     *     description="This endpoint exports all records from the new_customer table into a CSV file and returns the public URL for downloading the file.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="CSV file successfully generated and download URL returned",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="url", type="string", example="https://api.coreexl.com/osa_productionV2/public/storage/exports/new_customer_export_20251101_113545.csv")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to export data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to export data"),
     *             @OA\Property(property="message", type="string", example="SQLSTATE[HY000]: General error ...")
     *         )
     *     )
     * )
     */
    // public function export()
    // {
    //     try {
    //         $timestamp = now()->format('Ymd_His');
    //         $fileName = "new_customer_export_{$timestamp}.csv";
    //         $filePath = "public/exports/{$fileName}";

    //         // Ensure directory exists
    //         if (!Storage::exists('public/exports')) {
    //             Storage::makeDirectory('public/exports');
    //         }

    //         // Fetch data
    //         $customers = NewCustomer::select(
    //             'uuid',
    //             'osa_code',
    //             'name',
    //             'owner_name',
    //             'customer_type',
    //             'route_id',
    //             'warehouse',
    //             'approval_status',
    //             'reject_reason',
    //             'contact_no',
    //             'contact_no2',
    //             'is_whatsapp',
    //             'whatsapp_no',
    //             'street',
    //             'town',
    //             'landmark',
    //             'district',
    //             'payment_type',
    //             'creditday',
    //             'vat_no',
    //             'outlet_channel_id',
    //             'category_id',
    //             'subcategory_id',
    //             'longitude',
    //             'latitude',
    //             'credit_limit',
    //             'qr_code',
    //             'created_user',
    //             'updated_user',
    //             'status'
    //         )->get();

    //         // Define CSV columns
    //         $columns = [
    //             'UUID',
    //             'OSA Code',
    //             'Name',
    //             'Owner Name',
    //             'Customer Type',
    //             'Route ID',
    //             'Warehouse',
    //             'Approval Status',
    //             'Reject Reason',
    //             'Contact No',
    //             'Contact No 2',
    //             'Is WhatsApp',
    //             'WhatsApp No',
    //             'Street',
    //             'Town',
    //             'Landmark',
    //             'District',
    //             'Payment Type',
    //             'Credit Days',
    //             'VAT No',
    //             'Outlet Channel',
    //             'Category',
    //             'Subcategory',
    //             'Longitude',
    //             'Latitude',
    //             'Credit Limit',
    //             'QR Code',
    //             'Created By',
    //             'Updated By',
    //             'Status'
    //         ];

    //         $handle = fopen(storage_path("app/{$filePath}"), 'w');
    //         fputcsv($handle, $columns);

    //         foreach ($customers as $row) {
    //             fputcsv($handle, [
    //                 $row->uuid,
    //                 $row->osa_code,
    //                 $row->name,
    //                 $row->owner_name,
    //                 $row->customer_type,
    //                 $row->route_id,
    //                 $row->warehouse,
    //                 $row->approval_status,
    //                 $row->reject_reason,
    //                 $row->contact_no,
    //                 $row->contact_no2,
    //                 $row->is_whatsapp,
    //                 $row->whatsapp_no,
    //                 $row->street,
    //                 $row->town,
    //                 $row->landmark,
    //                 $row->district,
    //                 $row->payment_type,
    //                 $row->creditday,
    //                 $row->vat_no,
    //                 $row->outlet_channel_id,
    //                 $row->category_id,
    //                 $row->subcategory_id,
    //                 $row->longitude,
    //                 $row->latitude,
    //                 $row->credit_limit,
    //                 $row->qr_code,
    //                 $row->created_user,
    //                 $row->updated_user,
    //                 $row->status,
    //             ]);
    //         }

    //         fclose($handle);

    //         // Generate public URL
    //         $url = asset("storage/exports/{$fileName}");

    //         // Return response with URL
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Customer data exported successfully.',
    //             'url' => $url
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Failed to export data.',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
public function exportNewCustomer(Request $request)
{
    $uuid = $request->input('uuid');
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';

    $filename = 'newcustomer_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'newcustomerexports/' . $filename;

    $export = new NewCustomerFullExport($uuid);

    Excel::store(
        $export,
        $path,
        'public',
        $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX
    );

    // FIXED URL
    $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

    return response()->json([
        'status' => 'success',
        'uuid' => $uuid,
        'download_url' => $fullUrl,
    ]);
}
}
