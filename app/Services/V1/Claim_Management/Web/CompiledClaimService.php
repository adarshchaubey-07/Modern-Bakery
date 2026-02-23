<?php

namespace App\Services\V1\Claim_Management\Web;

use App\Models\Claim_Management\Web\CompiledClaim;
use Illuminate\Support\Facades\DB;

class CompiledClaimService
{
    public function getAll(int $perPage = 50, array $filters = [])
    {
        $query = CompiledClaim::query();

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

        $query->orderBy('created_at', 'DESC');

        return $query->paginate($perPage);
    }


    public function create(array $data)
    {
        try {
            $data = array_merge($data, [
                'osa_code' => $this->generateCode(),
            ]);
            return DB::transaction(function () use ($data) {
                // dd($data);
                return CompiledClaim::create($data);
            });
        } catch (Throwable $e) {

            Log::error('Compiled Claim Create Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function generateCode(): string
    {
        do {
            $last = CompiledClaim::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'COMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (CompiledClaim::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }
}
