<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\V1\Master\Mob\SalesmanMobResource;
use App\Http\Resources\V1\Master\Mob\DiscountHeaderResource;
use App\Services\V1\MasterServices\Mob\SalesmanMobService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Http\Resources\V1\Master\Mob\SalesmanAttendanceResource;
use App\Http\Resources\V1\Master\Mob\PromotionHeaderResource;
use App\Http\Resources\V1\Master\Mob\VisitCustomerResource;
use App\Http\Resources\V1\Master\Mob\PricingResource;
use App\Http\Requests\V1\MasterRequests\Mob\SalesmanAttendanceRequest;
use App\Http\Requests\V1\MasterRequests\Mob\UpdateSalesmanAttendanceRequest;
use App\Http\Requests\V1\MasterRequests\Mob\SalesmanRequest;
use Illuminate\Support\Facades\DB;
use App\Models\AgentCustomer;
use App\Models\PricingHeader;
use App\Models\DiscountHeader;

class SalesmanMobController extends Controller
{
     protected $service;

    public function __construct(SalesmanMobService $loginService)
    {
        $this->service = $loginService;
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/salesman/login",
     *     tags={"Salesman Authentication"},
     *     summary="Login API for salesman Mobile Api",
     *     description="Login by username, password and version check",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password","version"},
     *             @OA\Property(property="username", type="string", example="ST1000025"),
     *             @OA\Property(property="password", type="string", example="sales@123"),
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="token_no", type="string", example="abcdef123456cflgnlkdfglsdhfhkjlshg3215"),
     *             @OA\Property(property="device_no", type="string", example="ABC123XYZ", description="Unique device number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials or version"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'version'  => 'required|string',
            'token_no' => 'nullable|string',
            'device_no' => 'nullable|string',
        ]);

        $response = $this->service->login(
            $request->username,
            $request->password,
            $request->version,
            $request->token_no ?? null,
            $request->device_no ?? null
        );

        if (!$response['status']) {
            return response()->json([
                'status' => false,
                'message' => $response['message'],
                'latest_version' => $response['latest_version'] ?? null
            ], 401);
        }
        $salesman = $response['data'];
        $files = $this->generateSalesmanFiles($salesman);
        if ($response['status']) {
        return response()->json([
            'status' => true,
            'message' => $response['message'],
            'data' => new SalesmanMobResource($salesman),
            'customer_file_url' => $files['customer_file_url'] ?? null,
            'pricing_file_url' => $files['pricing_file_url'] ?? null,
            // 'discount_file_url' => $files['discount_file_url'] ?? null,
            ]);
    }
}
protected function generateSalesmanFiles($salesman)
    {
        $this->cleanupOldFiles();
        $files = [];
        $routeId = $salesman->route_id;
        $channelId = $salesman->channel_id;
        if (!$routeId) {
            return $files;
        }
        $customers = AgentCustomer::where('route_id', $routeId)
            ->whereNull('deleted_at')
            ->get();
        if ($customers->isEmpty()) {
            return $files;
        }
        $json = VisitCustomerResource::collection($customers)
            ->toJson(JSON_UNESCAPED_UNICODE);
        $fileName = 'salesman_files/customer_master_' . now()->format('Ymd_His') . '.txt';
        Storage::disk('public')->put($fileName, $json);
        $files['customer_file_url'] = 'storage/' . $fileName;
        $customerIds = $customers->pluck('id')->toArray();
        $customerIdsArray = '{' . implode(',', $customerIds) . '}';
        $pricingHeaders = PricingHeader::whereNull('deleted_at')
            ->where(function ($query) use ($channelId, $customerIdsArray) {
                $query->where('outlet_channel_id', (int) $channelId)
                ->orWhereRaw(
                    "EXISTS (
                        SELECT 1
                        FROM unnest(regexp_split_to_array(customer_id, '\s*,\s*')) AS cid
                        WHERE cid = ANY (?::text[])
                    )",
                    [$customerIdsArray]
                );
            })
            ->get();
        if ($pricingHeaders->isNotEmpty()) {
            $pricingJson = PricingResource::collection($pricingHeaders)
                ->toJson(JSON_UNESCAPED_UNICODE);
            $pricingFileName = 'salesman_files/pricing_' . now()->format('Ymd_His') . '.txt';
            Storage::disk('public')->put($pricingFileName, $pricingJson);
            $files['pricing_file_url'] = 'storage/' . $pricingFileName;
        }
        return $files;
    }
protected function cleanupOldFiles()
    {
        $files = Storage::disk('public')->listContents('salesman_files', false);
        $cutoff = now()->subDays(5)->timestamp;
        foreach ($files as $file) {
            if ($file['type'] === 'file' && $file['lastModified'] < $cutoff) {
                Storage::disk('public')->delete($file['path']);
            }
        }
    }

/**
 * @OA\Post(
 *     path="/mob/master_mob/salesman/attendance",
 *     summary="Create Salesman Attendance Record (Form Data)",
 *     tags={"Salesman Authentication"},
 *     description="Submit attendance data using multipart/form-data including optional check-in image.",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"salesman_id", "attendance_date"},
 *                 @OA\Property(property="salesman_id", type="integer", example=1),
 *                 @OA\Property(property="route_id", type="integer", example=2),
 *                 @OA\Property(property="warehouse_id", type="integer", example=3),
 *                 @OA\Property(property="attendance_date", type="string", format="date", example="2025-10-25"),
 *                 @OA\Property(property="time_in", type="string", format="date-time", example="2025-10-25 09:00:00"),
 *                 @OA\Property(property="latitude_in", type="number", format="float", example=26.9124),
 *                 @OA\Property(property="longitude_in", type="number", format="float", example=75.7873),
 *                 @OA\Property(
 *                     property="in_img",
 *                     type="string",
 *                     format="binary",
 *                     description="Check-in image file"
 *                 ),
 *                 @OA\Property(property="check_in", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Attendance created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Attendance record created successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="salesman_id", type="integer", example=1),
 *                 @OA\Property(property="attendance_date", type="string", example="2025-10-25"),
 *                 @OA\Property(property="check_in", type="boolean", example=true)
 *             )
 *         )
 *     )
 * )
 */
public function store(SalesmanAttendanceRequest $request)
{
    $data = $request->validated();
    $data['created_user'] = $data['salesman_id'];
    $data['updated_user'] = $data['salesman_id'];
    if ($request->hasFile('in_img')) {
        $data['in_img'] = $request->file('in_img')->store('attendance_images', 'public');
    }
    $attendance = $this->service->store($data);
    return response()->json([
        'status' => true,
        'message' => 'Attendance record created successfully',
        'data' => new SalesmanAttendanceResource($attendance),
    ], 201);
}
     /**
     * @OA\Get(
     *     path="/mob/master_mob/salesman/attendance-list",
     *     summary="Get Salesman Attendance List",
     *     tags={"Salesman Authentication"},
     *
     *     @OA\Parameter(
     *         name="salesman_id",
     *         in="query",
     *         description="Filter by Salesman ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="attendance_date",
     *         in="query",
     *         description="Filter by Date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attendance list fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attendance list fetched successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="salesman_id", type="integer", example=1),
     *                     @OA\Property(property="attendance_date", type="string", example="2025-10-25"),
     *                     @OA\Property(property="check_in", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
   public function index(Request $request)
    {
        $validated = $request->validate([
            'salesman_id' => 'nullable|integer',
            'attendance_date' => 'nullable|date',
        ]);
        $records = $this->service->list($validated);
        return response()->json([
            'status' => true,
            'message' => 'Attendance list fetched successfully',
            'data' => SalesmanAttendanceResource::collection($records),
        ]);
    }

    /**
 * @OA\Post(
 *    path="/mob/master_mob/salesman/update/{uuid}",
 *     summary="Update Salesman Attendance (Check-Out)",
 *     tags={"Salesman Authentication"},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the attendance record",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid", example="9f8a1c7e-1234-4d56-b789-0abc123def45")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="time_out", type="string", format="date-time", example="2025-10-25 18:00:00"),
 *                 @OA\Property(property="latitude_out", type="number", format="float", example=26.9124),
 *                 @OA\Property(property="longitude_out", type="number", format="float", example=75.7873),
 *                 @OA\Property(
 *                     property="out_img",
 *                     type="string",
 *                     format="binary",
 *                     description="Check-out image file"
 *                 ),
 *                 @OA\Property(property="check_out", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Attendance updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Attendance updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="time_out", type="string", example="2025-10-25 18:00:00"),
 *                 @OA\Property(property="check_out", type="boolean", example=true)
 *             )
 *         )
 *     )
 * )
 */
public function update(UpdateSalesmanAttendanceRequest $request, string $uuid)
{
    $data = $request->validated();
    if ($request->hasFile('out_img')) {
        $data['out_img'] = $request->file('out_img');
    }
    $attendance = $this->service->updateByUuid($uuid, $data);
    return response()->json([
        'status' => true,
        'message' => 'Attendance updated successfully',
        'data' => new SalesmanAttendanceResource($attendance),
    ]);
}


/**
 * @OA\Post(
 *     path="/mob/master_mob/salesman/today-visit",
 *     tags={"Salesman Authentication"},
 *     summary="Generate today's customer list for a salesman",
 *     description="This API generates a TXT file containing the list of customers assigned to a salesman for today's visit based on route, visit schedule, and current weekday. The response contains the URL to download the TXT file.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"salesman_id"},
 *             @OA\Property(
 *                 property="salesman_id",
 *                 type="integer",
 *                 example=5,
 *                 description="Unique ID of the salesman"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="TXT file generated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Today visit customer list generated successfully"),
 *             @OA\Property(property="file_url", type="string", example="storage/salesman_files/today_customers_20251126_143210.txt")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request payload or missing parameter",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The salesman_id field is required.")
 *         )
 *     )
 * )
 */
public function listTodayCustomers(Request $request)
{
    $request->validate([
        'salesman_id' => 'required|integer'
    ]);
    $salesman_id = $request->salesman_id;
    $customers = $this->service->getTodayCustomerList($salesman_id);
    $fileUrl = null;
    if ($customers->isNotEmpty()) {
        $today = Carbon::now()->format('l');
        $mapped = $customers->map(function ($visit) use ($today) {
            return [
                // 'route_visit_id'   => $visit['route_visit_id'],
                // 'customer_id'      => (string)$visit['customer_id'],
                // 'salesman_id'      => (string)($visit['salesman_id'] ?? ''),
                // 'merchandiser_id'  => (string)($visit['merchandiser_id'] ?? ''),
                // 'to_date'          => $visit['to_date'],
                // 'status'           => (string)$visit['status'],
                'customer_details' => $visit['customer_details'],
                'is_sequence'      => $visit['is_sequence'],
            ];
        });
        $jsonData = $mapped->toJson(JSON_UNESCAPED_UNICODE);
        $fileName = 'salesman_files/today_customers_' . now()->format('Ymd_His') . '.txt';
        Storage::disk('public')->put($fileName, $jsonData);
        $fileUrl = 'storage/' . $fileName;
    }
    return response()->json([
        'status'  => true,
        'message' => 'Today visit customer list generated successfully',
        'file_url' => $fileUrl
    ]);
}
/**
 * @OA\Post(
 *     path="/mob/master_mob/salesman/requested",
 *     summary="Create a new salesman request",
 *     description="Stores a new request made by a salesman",
 *     tags={"Salesman Authentication"},
 *     
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "salesman_id": 12,
 *                 "warehouse_id": 14,
 *                 "route_id": 3,
 *                 "manager_id": 7,
 *                 "requested_time": "14:30:00",
 *                 "requested_date": "2025-01-15"
 *             }
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=201,
 *         description="Requested record created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Requested record created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 example={
 *                     "id": 101,
 *                     "salesman_id": 12,
 *                     "warehouse_id": 14,
 *                     "route_id": 3,
 *                     "manager_id": 7,
 *                     "requested_time": "14:30:00",
 *                     "requested_date": "2025-01-15",
 *                 }
 *             )
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=422,
 *         description="Validation Error",
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "status": false,
 *                 "message": "The given data was invalid",
 *                 "errors": {
 *                     "salesman_id": {"The salesman id field is required."}
 *                 }
 *             }
 *         )
 *     )
 * )
 */
public function salesmanrequest(SalesmanRequest $request)
{
    $data = $request->validated();          
    $requested = $this->service->salesmanrequest($data);
    return response()->json([
        'status' => true,
        'message' => 'Requested record created successfully',
        'data' =>$requested,
    ], 201);
}
}

