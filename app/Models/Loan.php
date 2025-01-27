<?php

namespace App\Models;

use App\LoanStatus;
use Spatie\EventSourcing\Projections\Projection;

class Loan extends Projection
{
    protected $guarded = [];

    protected $casts = [
        'status' => LoanStatus::class,
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
