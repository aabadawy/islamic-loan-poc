<?php

namespace App\Models;

use Spatie\EventSourcing\Projections\Projection;

class LoanTransaction extends Projection
{
    protected $guarded = [];

    public function getKeyName()
    {
        return 'id';
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
