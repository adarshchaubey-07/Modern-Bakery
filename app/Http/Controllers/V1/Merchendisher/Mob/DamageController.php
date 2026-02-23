<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\DamageRequest;
use App\Services\V1\Merchendisher\Mob\DamageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DamageController extends Controller
{
    protected DamageService $damageService;

    public function __construct(DamageService $damageService)
    {
        $this->damageService = $damageService;
    }
 /**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/damage/create",
 *     tags={"Damage Stock"},
 *     summary="Create damage stock entry",
 *     description="Creates a new damage stock record",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={
 *                 "date",
 *                 "merchandisher_id",
 *                 "customer_id",
 *                 "item_id",
 *                 "shelf_id"
 *             },
 *             @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *             @OA\Property(property="merchandisher_id", type="integer", example=1),
 *             @OA\Property(property="customer_id", type="integer", example=5),
 *             @OA\Property(property="item_id", type="integer", example=20),
 *             @OA\Property(property="damage_qty", type="integer", example=2),
 *             @OA\Property(property="expiry_qty", type="integer", example=1),
 *             @OA\Property(property="salable_qty", type="integer", example=10),
 *             @OA\Property(property="shelf_id", type="integer", example=3)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Damage stock created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Damage stock created successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
    public function store(DamageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $damageStock = $this->damageService->create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Damage stock created successfully',
            'data'    => $damageStock
        ], 201);
    }
}