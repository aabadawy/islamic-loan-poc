<?php

namespace App\Models;

use App\CollectedMoneyStatus;
use Spatie\EventSourcing\Projections\Projection;

class LoanTransaction extends Projection
{
    protected $guarded = [];

    protected $casts = [
        'status' => CollectedMoneyStatus::class
    ];

    public function getKeyName()
    {
        return 'id';
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
