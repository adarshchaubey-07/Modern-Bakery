<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AccountGrp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountGrpService
{
   public function getList($perPage = 50, $isDropdown = false)
{
    $query = AccountGrp::query();

    if ($isDropdown) {
        return $query->orderBy('name')->get();
    }

    return $query
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->paginate($perPage, ['id', 'uuid','code', 'name', 'status']);
}
  public function create(array $data): AccountGrp
    {
        return DB::transaction(function () use ($data) {
            return AccountGrp::create([
                'code'         => $data['code'],
                'name'         => $data['name'],
                'status'       => $data['status'],
                // 'created_user' => Auth::id(),
            ]);
        });
    }
    public function findByUuid(string $uuid): ?AccountGrp
    {
        return AccountGrp::where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first(['id','uuid', 'code', 'name', 'status']);
    }

    public function updateByUuid(string $uuid, array $data): ?AccountGrp
    {
        return DB::transaction(function () use ($uuid, $data) {
            $accountGrp = AccountGrp::where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();

            if (!$accountGrp) {
                return null;
            }

            $accountGrp->update([
                'code'         => $data['code'],
                'name'         => $data['name'],
                'status'       => $data['status'],
                // 'updated_user' => Auth::id(),
            ]);

            return $accountGrp;
        });
    }
}