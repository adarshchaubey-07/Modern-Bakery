<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ItemExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Item::with([
            'itemCategory:id,category_name',
            'itemSubCategory:id,sub_category_name',
            'brandData:id,name',
            'itemUoms'
        ])->get();
    }

    public function map($item): array
    {
        $uom = $item->itemUoms->first();

        return [
            $item->id,
            $item->erp_code,
            $item->code,
            $item->name,
            $item->description,
            $item->image,
            optional($item->itemCategory)->category_name,
            optional($item->itemSubCategory)->sub_category_name, 
            $item->shelf_life,
            $item->status,
            optional($item->brandData)->name,
            $item->item_weight,
            $item->volume,
            $item->is_promotional,
            $item->is_taxable,
            $item->has_excies,
            $item->commodity_goods_code,
            $item->excise_duty_code,
            // $item->customer_code,
            $item->base_uom_vol,
            $item->alter_base_uom_vol,
            // $item->item_category,
            $item->distribution_code,
            $item->barcode,
            $item->net_weight,
            $item->tax,
            $item->vat,
            $item->excise,
            $item->uom_efris_code,
            $item->altuom_efris_code,
            $item->item_group,
            $item->item_group_desc,
            $item->caps_promo,
            $item->sequence_no,
            optional($uom)->id,
            optional($uom)->name,
            optional($uom)->uom_type,
            optional($uom)->upc,
            optional($uom)->price,
            optional($uom)->is_stock_keeping,
            optional($uom)->enable_for,
            optional($uom)->status,
            optional($uom)->keeping_quantity,
            optional($uom)->ref_id
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'ERP Code',
            'Code',
            'Name',
            'Description',
            'Image',
            'Category Name',
            'Sub Category Name',
            'Shelf Life',
            'Status',
            'Brand',
            'Item Weight',
            'Volume',
            'Is Promotional',
            'Is Taxable',
            'Has Excise',
            'Commodity Goods Code',
            'Excise Duty Code',
            // 'Customer Code',
            'Base UOM Vol',
            'Alt Base UOM Vol',
            // 'Item Category',
            'Distribution Code',
            'Barcode',
            'Net Weight',
            'Tax',
            'VAT',
            'Excise',
            'UOM Efris Code',
            'Alt UOM Efris Code',
            'Item Group',
            'Item Group Desc',
            'Caps Promo',
            'Sequence No',
            'UOM ID',
            'UOM Name',
            'UOM Type',
            'UPC',
            'Price',
            'Is Stock Keeping',
            'Enable For',
            'UOM Status',
            'Keeping Quantity',
            'UOM Ref ID'
        ];
    }
}
