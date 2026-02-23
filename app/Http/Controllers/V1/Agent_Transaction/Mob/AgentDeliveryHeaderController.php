<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Exports\AgentDeliveryExport;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\AgentDeliveryHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\Mob\AgentDeliveryHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\AgentDeliveryHeaderResource;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Services\V1\Agent_Transaction\Mob\AgentDeliveryHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


/**
 * @OA\Tag(
 *     name="Agent Delivery Header",
 *     description="API endpoints for managing Delivery Headers and their Details"
 * )
 */
class AgentDeliveryHeaderController extends Controller
{
    public function __construct(protected AgentDeliveryHeaderService $service) {}

    // /**
    //  * @OA\Get(
    //  *     path="/api/agent_transaction/agent-delivery/list",
    //  *     tags={"Agent Delivery Header"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="List all agent deliveries",
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of all agent deliveries"
    //  *     )
    //  * )
    //  */
    // public function index(): JsonResponse
    // {
    //     try {
    //         $headers = $this->service->all();
    //         return ResponseHelper::paginatedResponse(
    //             'Deliveries fetched successfully',
    //             AgentDeliveryHeaderResource::class,
    //             $headers
    //         );
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/mob/agent_transaction/agent-delivery/list",
     *     tags={"Mob Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="List all agent deliveries",
     *     @OA\Response(
     *         response=200,
     *         description="List of all agent deliveries"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->all($perPage);
            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}