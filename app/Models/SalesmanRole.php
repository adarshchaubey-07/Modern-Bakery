<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Traits\Blames;
use Illuminate\Support\Str;

class SalesmanRole extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'salesman_roles';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}