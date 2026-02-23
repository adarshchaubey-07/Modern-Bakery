<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class UomType extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'uom_types';

    protected $fillable = [
        'uom_type',
    ];
}