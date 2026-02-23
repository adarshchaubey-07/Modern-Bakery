<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Agent_Transaction\CapsCollectionHeader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use Illuminate\Support\Str;

class CapsCollectionDetail extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'caps_collection_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'uom_id',
        'collected_quantity',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(CapsCollectionHeader::class, 'header_id','id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(ItemUom::class, 'uom_id');
    }
}