<?php

namespace App\Models;

use App\LoanStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EventSourcing\Projections\Projection;

class Loan extends Projection
{
    protected $guarded = [];

    protected $casts = [
        'status' => LoanStatus::class,
    ];

    public function collectedTransactions(): HasMany
    {
        return $this->hasMany(LoanTransaction::class);
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
