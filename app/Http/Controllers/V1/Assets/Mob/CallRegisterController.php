<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Assets\Mob\CallRegisterResource;
use App\Services\V1\Assets\Mob\CallRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CallRegisterController extends Controller
{
        protected CallRegisterService $service;

    public function __construct(CallRegisterService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Get(
 *     path="/mob/master_mob/call-registers/pending-bd",
 *     tags={"Pending BD"},
 *     summary="Get Pending BD list",
 *     description="Returns all pending BD call registers",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Data fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Pending BD fetched successfully"),
 *             @OA\Property(
 *                 property="file_path",
 *                 type="string",
 *                 example="/var/www/html/project/storage/app/pending_BD/pending_bd_20260122_120000.txt"
 *             ),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(type="object")
 *             )
 *         )
 *     )
 * )
 */
public function index(): JsonResponse
{
    $records = $this->service->getAll();
    $dataArray = CallRegisterResource::collection($records)->resolve();
    $textContent = json_encode($dataArray, JSON_PRETTY_PRINT);
    $fileName = 'pending_bd_' . now()->format('Ymd_His') . '.txt';
    $filePath = "pending_BD/{$fileName}";
    Storage::disk('public')->put($filePath, $textContent);
    return response()->json([
        'status' => true,
        'message' => 'Pending BD fetched and saved successfully',
        'file_path' =>  "storage/{$filePath}",
    ], 200);
}
}