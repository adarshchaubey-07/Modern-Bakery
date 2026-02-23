<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\Damage;

class DamageService
{
    public function create(array $data)
    {
        return Damage::create($data);
    }

}