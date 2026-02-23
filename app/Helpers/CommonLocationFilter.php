<?php

// namespace App\Helpers;

// use App\Models\Warehouse;

// class CommonLocationFilter
// {
//     public static function resolveWarehouseIds(array $filter): array
//     {
//         // Priority: route > region > company

//         if (!empty($filter['route'])) {
//             return Warehouse::whereIn('id', self::ids($filter['route']))
//                 ->pluck('id')
//                 ->toArray();
//         }

//         if (!empty($filter['region'])) {
//             return Warehouse::whereHas('area', function ($q) use ($filter) {
//                 $q->whereIn('region_id', self::ids($filter['region']));
//             })->pluck('id')->toArray();
//         }

//         if (!empty($filter['company'])) {
//             return Warehouse::whereHas('area.region', function ($q) use ($filter) {
//                 $q->whereIn('company_id', self::ids($filter['company']));
//             })->pluck('id')->toArray();
//         }

//         return [];
//     }

//     private static function ids($value): array
//     {
//         if (empty($value)) {
//             return [];
//         }

//         if (is_array($value)) {
//             return array_values(
//                 array_filter(
//                     array_map('intval', $value)
//                 )
//             );
//         }

//         return array_values(
//             array_filter(
//                 array_map('intval', explode(',', (string) $value))
//             )
//         );
//     }
// }

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class CommonLocationFilter
{
    public static function apply(Builder $query, array $filters): Builder
    {
        $companyIds  = self::ids($filters['company_id']  ?? null);
        $regionIds   = self::ids($filters['region_id']   ?? null);
        $routeIds    = self::ids($filters['route_id']    ?? null);
        $salesmanIds = self::ids($filters['salesman_id'] ?? null);


        if (!empty($salesmanIds)) {
            return $query->whereIn('salesman_id', $salesmanIds);
        }

        if (!empty($routeIds)) {
            return $query->whereHas('salesman.route', function ($q) use ($routeIds) {
                $q->whereIn('id', $routeIds);
            });
        }

        if (!empty($regionIds)) {
            return $query->whereHas('salesman.route.region', function ($q) use ($regionIds) {
                $q->whereIn('id', $regionIds);
            });
        }

        if (!empty($companyIds)) {
            return $query->whereHas('salesman.route.region', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        return $query;
    }

    private static function ids($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        return array_values(
            array_filter(
                array_map('intval', explode(',', (string) $value))
            )
        );
    }
}
