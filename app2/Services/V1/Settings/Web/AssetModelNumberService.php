<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AsModelNumber;
use Illuminate\Support\Facades\DB;
use Exception;

class AssetModelNumberService
{
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $model = AsModelNumber::create($data);

            DB::commit();

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Model number created successfully',
                'data'    => $model
            ];
        } catch (Exception $e) {

            DB::rollBack();
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    public function list(int $perPage = 50)
    {
        try {
            $result = AsModelNumber::orderBy('id', 'desc')->paginate($perPage);

            return [
                'status' => 'success',
                'code'   => 200,
                'message' => 'Model numbers fetched successfully',
                'data'   => $result->items(),
                'pagination' => [
                    'page'         => $result->currentPage(),
                    'limit'        => $result->perPage(),
                    'totalPages'   => $result->lastPage(),
                    'totalRecords' => $result->total(),
                ]
            ];
        } catch (Exception $e) {

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }
}
