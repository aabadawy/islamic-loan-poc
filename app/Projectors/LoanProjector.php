<?php

namespace App\Projectors;

use App\Events\LoanRequested;
use App\LoanStatus;
use App\Models\Loan;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class LoanProjector extends Projector
{
    public function onLoanRequested(LoanRequested $event): void
    {
        (new Loan)->writeable()->create([
            'id' => $event->loanUuid,
            'user_id' => $event->userId,
            'requested_amount' => $event->requestedAmount,
            'daily_amount' => $event->dailyAmount,
            'status' => LoanStatus::Requested,
            'created_at' => $event->date,
            'requested_at' => $event->date,
        ]);
    }
}
