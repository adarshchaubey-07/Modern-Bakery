<?php

namespace App\Services\V1\Claim_Management\Web;

use App\Models\Claim_Management\Web\PetitClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Throwable;

class PetitClaimService
{
    public function create(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {

                // Handle Image Upload
                if (!empty($data['claim_file']) && $data['claim_file']->isValid()) {

                    // Random filename
                    $filename = Str::random(20) . '.' . $data['claim_file']->getClientOriginalExtension();
                    $relativePath = 'petit_claims/' . $filename;

                    // Store file in /storage/app/public/petit_claims
                    $data['claim_file']->storeAs('petit_claims', $filename, 'public');

                    // Full public URL
                    $appUrl = rtrim(config('app.url'), '/');
                    $data['claim_file'] = $appUrl . '/storage/app/public/' . $relativePath;
                }

                // Auto OSA Code
                $data['osa_code'] = $this->generateCode();
                // dd($data);
                return PetitClaim::create($data);
            });
        } catch (Throwable $e) {

            Log::error('Petit Claim Create Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }


    public function generateCode(): string
    {
        do {
            $last = PetitClaim::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'COMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (PetitClaim::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }
    public function getAll(int $perPage = 50, array $filters = [])
    {
        $query = PetitClaim::query();

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['month_range'])) {
            $query->where('month_range', $filters['month_range']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    }
}
