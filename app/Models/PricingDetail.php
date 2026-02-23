<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PricingDetail extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'pricing_details';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'header_id',
        'item_id',
        'price',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'uom_id',
    ];

    protected static function booted()
{
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    });
}
    public function header()
    {
        return $this->belongsTo(PricingHeader::class, 'header_id');
    }

    // Relation to Item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
        public function itemUoms()
    {
        return $this->belongsTo(ItemUOM::class, 'item_id', 'item_id');
    }
    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_user');
    }
}
