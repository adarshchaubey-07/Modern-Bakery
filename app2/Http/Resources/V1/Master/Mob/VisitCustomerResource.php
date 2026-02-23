<?php
namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitCustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'customer_id'  => $this->customer_id,
            'salesman_id'  => $this->salesman_id,
            'to_date'      => $this->to_date,
            'status'       => $this->status,
            // 'days'         => $this->days,
            
            // Include nested customer details (from relation)
            'customer' => [
                'name'    => $this->agentCustomer->name ?? null,
                'code'    => $this->agentCustomer->osa_code ?? null,
                'landmark' => $this->agentCustomer->landmark ?? null,
                'contact' => $this->agentCustomer->contact_no ?? null,
                'longitude'=> $this->agentCustomer->longitude?? null,
                'latitude'=> $this->agentCustomer->latitude?? null,
            ]
        ];
    }
}
