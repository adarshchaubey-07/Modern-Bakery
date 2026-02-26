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
                '7',
                '19',
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
                '7',
                '19',
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
                ->orderBy('route_name', 'asc');

            $query = DataAccessHelper::filterRoutes($query, $user);

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['route_name', 'route_code'])) {
                        $query->whereRaw(
                            "LOWER({$field}) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->get();
        }
        $query = Route::with([
            'region:id,region_code,region_name',
            'vehicle:id,vehicle_code,number_plat',
            'getrouteType:id,route_type_code,route_type_name',
        ])
        ->withCount([
            'customers as customers_count' => function ($q) {
                $q->where('status', 1);
            }
        ])
        ->orderBy('id', 'desc');

        $query = DataAccessHelper::filterRoutes($query, $user);

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['route_name', 'route_code'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }
        }

        if (isset($filters['status']) && (int) $filters['status'] === 0) {
            $query->orderByRaw("
                CASE
                    WHEN status = 0 THEN 0
                    WHEN status = 1 THEN 1
                END
            ");
        } else {
            $query->orderByRaw("
                CASE
                    WHEN status = 1 THEN 0
                    WHEN status = 0 THEN 1
                END
            ");
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
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            },
            'region' => function ($q) {
                $q->select('id', 'region_code', 'region_name');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'name', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'name', 'username');
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
                'getrouteType' => function ($q) {
                    $q->select('id', 'route_type_code', 'route_type_name');
                },
                'region' => function ($q) {
                    $q->select('id', 'region_code', 'region_name');
                }
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $like = "%{$searchTerm}%";

                $query->where(function ($q) use ($like) {
                    $q->orWhereRaw("LOWER(route_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(route_code) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(description) LIKE ?", [$like])

                        ->orWhereHas('getrouteType', function ($r) use ($like) {
                            $r->whereRaw("LOWER(route_type_name) LIKE ?", [$like])
                                ->orWhereRaw("LOWER(route_type_code) LIKE ?", [$like]);
                        })
                         ->orWhereHas('region', function ($r) use ($like) {
                            $r->whereRaw("LOWER(region_code) LIKE ?", [$like])
                                ->orWhereRaw("LOWER(region_name) LIKE ?", [$like]);
                        })
                        ->orWhereHas('vehicle', function ($r) use ($like) {
                            $r->whereRaw("LOWER(vehicle_code) LIKE ?", [$like])
                                ->orWhereRaw("LOWER(number_plat) LIKE ?", [$like]);
                        });
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search routes: " . $e->getMessage());
        }
    }

public function exportRoutes($startDate = null, $endDate = null, $searchTerm = null)
{
    $query = Route::with([
        'vehicle:id,vehicle_code,number_plat',
        'getrouteType:id,route_type_code,route_type_name',
        'region:id,region_code,region_name',
    ]);

    if ($startDate && $endDate) {
        $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    if (!empty($searchTerm)) {
        $searchTerm = strtolower($searchTerm);
        $like = "%{$searchTerm}%";

        $query->where(function ($q) use ($like) {
            $q->orWhereRaw("LOWER(route_name) LIKE ?", [$like])
              ->orWhereRaw("LOWER(route_code) LIKE ?", [$like])
              ->orWhereRaw("LOWER(description) LIKE ?", [$like])

              ->orWhereHas('getrouteType', function ($r) use ($like) {
                  $r->whereRaw("LOWER(route_type_name) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(route_type_code) LIKE ?", [$like]);
              })
             ->orWhereHas('region', function ($r) use ($like) {
                  $r->whereRaw("LOWER(region_code) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(region_name) LIKE ?", [$like]);
              })
              ->orWhereHas('vehicle', function ($v) use ($like) {
                  $v->whereRaw("LOWER(vehicle_code) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(number_plat) LIKE ?", [$like]);
              });
        });
    }

    return $query->get()->map(function ($route) {
        return [
            'route_code'     => $route->route_code,
            'route_name'     => $route->route_name,
            'description'    => $route->description,
            'route_type'     => optional($route->getrouteType)->route_type_name,
            'region'         => optional($route->region)->region_name,
            'vehicle_name'   => optional($route->vehicle)->vehicle_code,
            'status'         => $route->status == 1 ? 'Active' : 'Inactive',
        ];
    });
}

    public function bulkUpdateStatus(array $ids, $status): int
    {
        return Route::whereIn('id', $ids)->update(['status' => $status]);
    }
}
