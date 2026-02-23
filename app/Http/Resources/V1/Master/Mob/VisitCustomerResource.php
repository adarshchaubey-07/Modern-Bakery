<?php
namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitCustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
             'id'                => $this->id,
            'osa_code'          => $this->osa_code,
            'customer_type'     => $this->customer_type,
            'route_id'          => $this->route_id,
            'name'              => $this->name,
            'owner_name'        => $this->owner_name,
            'contact_no'        => $this->contact_no,
            'contact_no2'       => $this->contact_no2,
            'whatsapp_no'       => $this->whatsapp_no,
            'is_whatsapp'       => $this->is_whatsapp,
            'street'            => $this->street,
            'town'              => $this->town,
            'city'              => $this->city,
            'district'          => $this->district,
            'landmark'          => $this->landmark,
            'region_id'         => $this->region_id,

            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,

            'outlet_channel_id' => $this->outlet_channel_id,
            'category_id'       => $this->category_id,
            'cust_group'        => $this->cust_group,
            'account_group'     => $this->account_group,

            'creditday'         => $this->creditday,
            'credit_limit'      => $this->credit_limit,
            'payment_type'      => $this->payment_type,
            'is_cash'           => $this->is_cash,
            'tin_no'            => $this->tin_no,
            'qr_code'           => $this->qr_code,
            'language'          => $this->language,
            'is_driver'         => $this->is_driver,
        ];
    }
}
