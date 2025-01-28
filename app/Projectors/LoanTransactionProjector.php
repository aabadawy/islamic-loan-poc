<?php

namespace App\Projectors;

use App\Events\MoneyCollected;
use App\Models\LoanTransaction;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class LoanTransactionProjector extends Projector
{
    public function onMoneyCollected(MoneyCollected $event)
    {
        (new LoanTransaction)
            ->writeable()
            ->create([
                'id' => $event->transactionId,
                'loan_id' => $event->loanId,
                'amount' => $event->amount,
                'created_at' => $event->collectedAt,
                'updated_at' => $event->collectedAt,
            ]);
    }
}
