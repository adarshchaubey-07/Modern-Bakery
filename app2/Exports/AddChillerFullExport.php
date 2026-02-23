<?php

namespace App\Exports;

use App\Models\AddChiller;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AddChillerFullExport implements FromArray, WithHeadings
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function array(): array
    {
        $query = AddChiller::with([
            'country',
            'vendor',
            'assetsCategory',
            'modelNumber',
            'manufacture',
            'brand'
        ]);

        if ($this->uuid) {
            $query->where('uuid', $this->uuid);
        }

        $chillers = $query->get();

        $data = [];

        foreach ($chillers as $c) {
            $data[] = [
                'id'         => $c->id,
                'uuid'       => $c->uuid,
                'osa_code'   => $c->osa_code,
                'sap_code'   => $c->sap_code,
                'serial_number' => $c->serial_number,
                'acquisition'   => $c->acquisition,
                'assets_type'   => $c->assets_type,

                // Country
                'country_code' => $c->country->country_code ?? null,
                'country_name' => $c->country->country_name ?? null,

                // Vendor
                'vendor_code' => $c->vendor->code ?? null,
                'vendor_name' => $c->vendor->name ?? null,

                // Assets Category
                'assets_category_code' => $c->assetsCategory->osa_code ?? null,
                'assets_category_name' => $c->assetsCategory->name ?? null,

                // Model Number
                'model_number_code' => $c->modelNumber->code ?? null,
                'model_number_name' => $c->modelNumber->name ?? null,

                // Manufacturer
                'manufacturer_code' => $c->manufacture->osa_code ?? null,
                'manufacturer_name' => $c->manufacture->name ?? null,

                // Branding
                'brand_code' => $c->brand->osa_code ?? null,
                'brand_name' => $c->brand->name ?? null,

                'status'        => $c->status,
                'remarks'       => $c->remarks,
                'trading_partner_number' => $c->trading_partner_number,
                'capacity'      => $c->capacity,
                'manufacturing_year' => $c->manufacturing_year,
                'created_at'    => $c->created_at,
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'OSA Code',
            'SAP Code',
            'Serial Number',
            'Acquisition',
            'Assets Type',

            'Country Code',
            'Country Name',

            'Vendor Code',
            'Vendor Name',

            'Assets Category Code',
            'Assets Category Name',

            'Model Number Code',
            'Model Number Name',

            'Manufacturer Code',
            'Manufacturer Name',

            'Brand Code',
            'Brand Name',

            'Status',
            'Remarks',
            'Trading Partner Number',
            'Capacity',
            'Manufacturing Year',
            'Created At',
        ];
    }
}
