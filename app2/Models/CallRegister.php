<?php

namespace App\Models;

use App\Traits\Blames;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallRegister extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_call_register';

    protected $fillable = [
        'uuid',
        'osa_code',
        'ticket_type',
        'ticket_date',
        'technician_id',
        'sales_valume',
        'ctc_status',
        'chiller_serial_number',
        'asset_number',
        'model_number',
        'chiller_code',
        'branding',
        'outlet_name',
        'owner_name',
        'road_street',
        'town',
        'landmark',
        'outlet_code',
        'customer_id',
        'fridge_id',
        'district',
        'contact_no1',
        'contact_no2',
        'current_outlet_code',
        'current_outlet_name',
        'current_owner_name',
        'current_road_street',
        'current_town',
        'current_landmark',
        'current_district',
        'current_contact_no1',
        'current_contact_no2',
        'current_depot',
        'current_asm',
        'current_rm',
        'nature_of_call',
        'follow_up_action',
        'followup_status',
        'status',
        'call_category',
        'reason_for_cancelled',
        'created_date',
        'completion_date',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    public $timestamps = true;

    protected $dates = ['deleted_at'];

    public function technician()
    {
        return $this->belongsTo(Salesman::class, 'technician_id');
    }
}
