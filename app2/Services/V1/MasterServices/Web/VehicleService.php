<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Exports\VehiclesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\DataAccessHelper;
use App\Helpers\LogHelper;

class VehicleService
{
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $user = auth()->user();
            if ($dropdown) {
                $query = Vehicle::select(['id', 'vehicle_code', 'number_plat', 'status'])->where('status', 1)
                    ->orderBy('number_plat', 'asc');
                foreach ($filters as $field => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    if (in_array($field, ['vehicle_code', 'number_plat'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } elseif ($field === 'warehouse_id') {
                        $query->where('warehouse_id', $value);
                    } elseif ($field === 'status') {
                        $query->where('status', $value);
                    }
                }
                return $query->get();
            }
            $query = Vehicle::with([
                'warehouse:id,warehouse_code,warehouse_name,owner_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username'
            ]);
            $query = DataAccessHelper::filterVehicles($query, $user);
            foreach ($filters as $field => $value) {
                if (empty($value)) {
                    continue;
                }
                if (in_array($field, ['vehicle_name', 'vehicle_code', 'number_plat'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif ($field === 'warehouse_id') {
                    $query->where('warehouse_id', $value);
                } elseif ($field === 'status') {
                    $query->where('status', $value);
                } else {
                    $query->where($field, $value);
                }
            }
            $query->orderBy('id', 'desc');
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch vehicles: " . $e->getMessage());
        }
    }
    public function findByUuid(string $uuid): ?Vehicle
    {
        try {
            return Vehicle::with(['warehouse:id,warehouse_name'])
                ->where('uuid', $uuid)
                ->first(); // fetch by UUID
        } catch (Exception $e) {
            throw new Exception("Failed to fetch vehicle: " . $e->getMessage());
        }
    }
    protected function generateVehicleCode(): string
    {
        $lastVehicle = Vehicle::withTrashed()
            ->select('vehicle_code')
            ->orderByDesc('vehicle_code')
            ->first();
        if ($lastVehicle) {
            $lastNumber = (int) Str::after($lastVehicle->vehicle_code, 'VC');
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        return 'VC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Vehicle
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception("Unauthenticated: No user logged in.");
            }

            if (empty($data['vehicle_code'])) {
                $data['vehicle_code'] = $this->generateVehicleCode();
            }

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $vehicle = Vehicle::create($data);

            DB::commit();
            LogHelper::store(
                'master',
                'vehicles',
                'add',
                null,
                $vehicle->toArray(),
                $userId
            );

            return $vehicle->fresh()->load('warehouse:id,warehouse_name');
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Vehicle create failed: ' . $e->getMessage(), ['data' => $data]);

            throw new Exception("Failed to create vehicle: " . $e->getMessage());
        }
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception("Unauthenticated: No user logged in.");
            }
            $data['updated_user'] = $userId;
            if (isset($data['vehicle_code'])) {
                unset($data['vehicle_code']);
            }
            $vehicle->fill($data);
            $vehicle->save();
            DB::commit();

            return $vehicle->fresh()->load('warehouse:id,warehouse_name');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Vehicle update failed: ' . $e->getMessage(), [
                'vehicle_id' => $vehicle->id ?? null,
                'data' => $data
            ]);
            throw new Exception("Failed to update vehicle: " . $e->getMessage());
        }
    }

    public function delete(Vehicle $vehicle): bool
    {
        try {
            return $vehicle->delete();
        } catch (Exception $e) {
            throw new Exception("Failed to delete vehicle: " . $e->getMessage());
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = Vehicle::with([
                'warehouse:id,warehouse_code,warehouse_name,owner_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ]);

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . strtolower($searchTerm) . '%';

                    $q->orWhereRaw("LOWER(vehicle_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(number_plat) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_chesis_no) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_type) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_brand) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(capacity) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch vehicles: " . $e->getMessage());
        }
    }

    public function export($filters, $format)
    {
        $filename = 'vehicles_' . now()->format('Ymd_His');
        $filePath = "exports/$filename";
        if ($format === 'pdf') {
            $query = Vehicle::with(['warehouse', 'createdBy', 'updatedBy']);
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['vehicle_name', 'vehicle_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            $data = $query->get();
            $pdf = Pdf::loadView('pdf.vehicles', ['data' => $data]);
            $filePath .= '.pdf';
            Storage::disk('public')->put($filePath, $pdf->output());
        } elseif ($format === 'xlsx') {
            $filePath .= '.xlsx';
            Excel::store(new VehiclesExport($filters), $filePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
        } else {
            $filePath .= '.csv';
            Excel::store(new VehiclesExport($filters), $filePath, 'public', \Maatwebsite\Excel\Excel::CSV);
        }

        // ✅ Manually build the file URL
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $filePath; // assumes `php artisan storage:link` exists

        return $fullUrl;
        // return Storage::disk('public')->url($filePath);
    }
    public function updateVehiclesStatus(array $vehicleIds, $status)
    {
        $updated = Vehicle::whereIn('id', $vehicleIds)->update(['status' => $status]);
        return $updated > 0;
    }
}
