<?php

namespace App\Projectors;

use App\Events\LoanApproved;
use App\Events\LoanPaid;
use App\Events\LoanRequested;
use App\Events\LoanRequestedAmountChanged;
use App\Events\MoneyCollected;
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
            'remaining_amount' => $event->requestedAmount,
            'daily_amount' => $event->dailyAmount,
            'status' => LoanStatus::Requested,
            'created_at' => $event->date,
            'requested_at' => $event->date,
        ]);
    }

    public function onLoanRequestedAmountChanged(LoanRequestedAmountChanged $event): void
    {
        Loan::find($event->loanId)
            ->writeable()->update([
                'requested_amount' => $event->requestedAmount,
                'remaining_amount' => $event->requestedAmount,
                'daily_amount' => $event->dailyAmount,
                'status' => LoanStatus::Requested,
            ]);
    }

    public function onLoanApproved(LoanApproved $event): void
    {
        Loan::find($event->loanId)
            ->writeable()->update(['status' => LoanStatus::Approved]);
    }

    public function onMoneyCollected(MoneyCollected $event): void
    {
        Loan::find($event->loanId)
            ->writeable()
            ->decrement('remaining_amount', $event->amount, [
                'status' => LoanStatus::Partial_Paid,
            ]);
    }

    public function onLoanPaid(LoanPaid $event): void
    {
        Loan::find($event->loanId)
            ->writeable()
            ->update(['status' => LoanStatus::Paid]);
    }
}
