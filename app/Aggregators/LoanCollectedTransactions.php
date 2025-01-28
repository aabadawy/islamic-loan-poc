<?php

namespace App\Aggregators;

use App\Events\MoneyCollected;
use Carbon\Carbon;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

class LoanCollectedTransactions extends AggregatePartial
{
    protected array $paidTransactions = [];

    public function noMoneyCollectedYet(): bool
    {
        return empty($this->paidTransactions);
    }

    public function collectMoney(string $transactionId, float $collectedAmount, Carbon $collectedAt): self
    {
        $this->recordThat(new MoneyCollected($this->aggregateRootUuid(), $transactionId, $collectedAmount, $collectedAt));

        return $this;
    }

    public function applyMoneyCollected(MoneyCollected $event)
    {
        $this->paidTransactions[$event->transactionId] = ['amount' => $event->amount, 'collectedAt' => $event->collectedAt];
    }
}
