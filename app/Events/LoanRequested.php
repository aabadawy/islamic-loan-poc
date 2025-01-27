<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class LoanRequested extends ShouldBeStored
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $loanUuid,
        public int $userId,
        public float $requestedAmount,
        public float $dailyAmount,
        public Carbon $date
    ) {
        //
    }
}
