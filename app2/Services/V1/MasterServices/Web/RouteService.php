<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\LogHelper;

class RouteService
{
    protected function generateRouteCode(): string
    {
        $lastRoute = Route::orderByDesc('id')->first();
        $nextId = $lastRoute ? $lastRoute->id + 1 : 1;
        return 'RT' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Route
{
    DB::beginTransaction();

    try {
        $data['created_user'] = Auth::id();
        $data['updated_user'] = Auth::id();

        if (!isset($data['route_code']) || empty($data['route_code'])) {
            $data['route_code'] = $this->generateRouteCode();
        }

        if (isset($data['route_type']) && is_array($data['route_type'])) {
            $data['route_type'] = json_encode($data['route_type']);
        }

        $route = Route::create($data);

        DB::commit();
        LogHelper::store(
            'master',           
            'routes',         
            'add',            
            null,             
            $route->toArray(), 
            Auth::id()
        );

        return $route->fresh();

    } catch (Exception $e) {

        DB::rollBack();
        Log::error('Route create failed: ' . $e->getMessage(), ['data' => $data]);

        throw $e;
    }
}

public function update(Route $route, array $data): Route
{
    DB::beginTransaction();
    try {
        $previousData = $route->toArray();
        $data['updated_user'] = Auth::id();

        if (isset($data['route_code'])) {
            unset($data['route_code']);
        }

        $route->fill($data);
        $route->save();

        DB::commit();
        $currentRoute = $route->fresh();
        $currentData  = $currentRoute->toArray();
        LogHelper::store(
            'master',      
            'routes',    
            'update',   
            $previousData, 
            $currentData, 
            Auth::id()
        );

        return $currentRoute;

    } catch (\Exception $e) {

        DB::rollBack();
        Log::error('Route update failed: ' . $e->getMessage(), [
            'route_id' => $route->id ?? null,
            'data'     => $data
        ]);

        throw $e;
    }
}
    public function delete(Route $route): void
    {
        DB::beginTransaction();
        try {
            // dd($route->delete());
            $route->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Route delete failed: ' . $e->getMessage(), ['route_id' => $route->id ?? null]);
            throw $e;
        }
    }

public function getAll($perPage = 50, $filters = [], $dropdown = false)
{
    try {
        $user = auth()->user();
        if ($dropdown) {
            $query = Route::select(['id', 'route_code', 'route_name'])
                ->with([
                    'warehouse:id,warehouse_name',
                ])
                ->orderBy('route_name', 'asc');
            $query = DataAccessHelper::filterRoutes($query, $user);
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if ($field === 'warehouse_id') {
                        if (is_string($value) && strpos($value, ',') !== false) {
                            $value = array_map('intval', explode(',', $value));
                            $value = array_filter($value, fn($v) => $v > 0);
                        }
                        if (is_array($value)) {
                            $query->whereIn('warehouse_id', $value);
                        } else {
                            $query->where('warehouse_id', $value);
                        }
                    } elseif (in_array($field, ['route_name', 'route_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            return $query->get();
        }
        $query = Route::with([
            'vehicle:id,vehicle_code,number_plat',
            'warehouse:id,warehouse_code,warehouse_name,owner_name',
            'getrouteType:id,route_type_code,route_type_name',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ]);
        $query = DataAccessHelper::filterRoutes($query, $user);
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['route_name', 'route_code'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif ($field === 'warehouse_id') {
                    if (is_string($value) && strpos($value, ',') !== false) {
                        $value = array_map('intval', explode(',', $value));
                        $value = array_filter($value, fn($v) => $v > 0);
                    }
                    if (is_array($value)) {
                        $query->whereHas('warehouse', function ($q) use ($value) {
                            $q->whereIn('id', $value);
                        });
                    } else {
                        $query->whereHas('warehouse', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    }
                } else {
                    $query->where($field, $value);
                }
            }
        }
        return $query->paginate($perPage);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch routes: " . $e->getMessage());
    }
}
public function getByUuid(string $uuid): Route
    {
        return Route::with([
            'vehicle' => function ($q) {
                $q->select('id', 'vehicle_code', 'number_plat');
            },
            'warehouse' => function ($q) {
                $q->select('id', 'warehouse_code', 'warehouse_name', 'owner_name');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            },
            'createdBy' => function ($q) {
                $q->select('id','name', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id','name', 'username');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            }
        ])->where('uuid', $uuid)->firstOrFail();
    }
public function globalSearch($perPage = 10, $searchTerm = null)
   {
    try {

        $query = Route::with([
            'vehicle' => function ($q) {
                $q->select('id', 'vehicle_code', 'number_plat');
            },
            'warehouse' => function ($q) {
                $q->select('id', 'warehouse_code', 'warehouse_name', 'owner_name');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'name', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'name', 'username');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            }
        ]);

        if (!empty($searchTerm)) {
            $searchTerm = strtolower($searchTerm);
            $like = "%{$searchTerm}%";

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw("LOWER(route_name) LIKE ?", [$like])
                  ->orWhereRaw("LOWER(route_code) LIKE ?", [$like])
                  ->orWhereRaw("LOWER(description) LIKE ?", [$like])

                  // warehouse table
                  ->orWhereHas('warehouse', function ($w) use ($like) {
                      $w->whereRaw("LOWER(warehouse_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(owner_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(warehouse_code) LIKE ?", [$like]);
                  })

                  // route type table
                  ->orWhereHas('getrouteType', function ($r) use ($like) {
                      $r->whereRaw("LOWER(route_type_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(route_type_code) LIKE ?", [$like]);
                  });
            });
        }

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        throw new \Exception("Failed to search routes: " . $e->getMessage());
    }
}


    public function exportRoutes($startDate, $endDate)
    {
        $routes = Route::with(['warehouse']) // if there's a relation
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(), 
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        return $routes->map(function ($route) {
            return [
                'route_code'     => $route->route_code,
                'route_name'     => $route->route_name,
                'description'    => $route->description,
                'warehouse_name' => $route->warehouse->warehouse_name ?? null,
                'route_type'     => $route->getrouteType->route_type_name,
                'vehicle_name'   => $route->vehicle->vehicle_code ?? null,
                'status'         => $route->status
            ];
        });
    }

    public function bulkUpdateStatus(array $ids, $status): int
        {
            // Update the 'status' for multiple routes at once, return the number affected
            return Route::whereIn('id', $ids)->update(['status' => $status]);
        }

} 