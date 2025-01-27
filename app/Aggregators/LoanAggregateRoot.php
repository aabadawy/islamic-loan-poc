<?php

namespace App\Aggregators;

use App\Events\LoanRequested;
use Carbon\Carbon;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class LoanAggregateRoot extends AggregateRoot
{
    public function requestLoan(int $userId, float $requestedAmount, float $dailyAmount, Carbon $date)
    {
        $this->recordThat(new LoanRequested($this->uuid(), $userId, $requestedAmount, $dailyAmount, $date));

        return $this;
    }
}
