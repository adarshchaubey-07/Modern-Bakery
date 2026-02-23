<?php

// namespace App\Services;

// use Illuminate\Support\Facades\DB;
// use App\Models\CodeCounter;
// use Carbon\Carbon;

// class CodeGeneratorService
// {
//     public function generateNextCode(string $prefix): string
//     {
//         $currentYear = Carbon::now()->year;
//         $currentDate = Carbon::now()->format('Ymd');
//         return DB::transaction(function () use ($prefix, $currentYear, $currentDate) {
//             $counter = CodeCounter::query()
//                 ->where('prefix', $prefix)
//                 ->where('year', $currentYear)
//                 ->lockForUpdate()
//                 ->first();
//             if (!$counter) {
//                 $counter = CodeCounter::create([
//                     'prefix' => $prefix,
//                     'year' => $currentYear,
//                     'current_value' => 1
//                 ]);
//             }
//             $nextValue = $counter->current_value;
//             $counter->current_value += 1;
//             $counter->save();
//             $paddedValue = str_pad($nextValue, 6, '0', STR_PAD_LEFT);

//             return "{$prefix}-{$currentDate}-{$paddedValue}";
//         });
//     }
// }

namespace App\Services\V1;

use Illuminate\Support\Facades\DB;
use App\Models\CodeCounter;
use Carbon\Carbon;

class CodeGeneratorService
{
    // public function generateNextCodeAtomically(string $prefix): string
    // {
    //     $currentYear = Carbon::now()->year;
    //     $currentDate = Carbon::now()->format('Ymd');
    //     return DB::transaction(function () use ($prefix, $currentYear, $currentDate) {
    //         $counter = CodeCounter::query()
    //         ->where('prefix', $prefix)
    //         ->where('year', $currentYear)
    //         ->lockForUpdate() 
    //         ->first();

    //         if (!$counter) {
    //             $counter = CodeCounter::create([
    //                 'prefix' => $prefix,
    //                 'year' => $currentYear,
    //                 'current_value' => 1
    //             ]);
    //         }
    //         $nextValue = $counter->current_value;
    //         $counter->current_value += 1;
    //         $counter->save();
    //         $paddedValue = str_pad($nextValue, 6, '0', STR_PAD_LEFT);

    //         return "{$prefix}{$paddedValue}";
    //     }); 
    // }

    public function generateNextCodeAtomically(string $prefix): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($prefix, $year) {

            $counter = CodeCounter::where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                try {
                    $counter = CodeCounter::create([
                        'prefix'        => $prefix,
                        'year'          => $year,
                        'current_value' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $counter = CodeCounter::where('prefix', $prefix)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();
                }
            }

            $next = $counter->current_value;

            $counter->increment('current_value');

            // ✅ INCLUDE YEAR IN CODE
            // return sprintf(
            //     '%s%d%s',
            //     $prefix,
            //     $year,
            //     str_pad($next, 6, '0', STR_PAD_LEFT)
            // );
            $yearSum = array_sum(str_split($year));

            return sprintf(
                '%s%d%s',
                $prefix,
                $yearSum,
                str_pad($next, 6, '0', STR_PAD_LEFT)
            );
        });
    }
}
