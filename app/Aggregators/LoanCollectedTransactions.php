<?php

namespace App\Aggregators;

use App\CollectedMoneyStatus;
use App\Events\MoneyCollected;
use Carbon\Carbon;
use Spatie\EventSourcing\AggregateRoots\AggregatePartial;

/**
 * @property LoanAggregateRoot $aggregateRoot
 */
class LoanCollectedTransactions extends AggregatePartial
{
    protected array $paidTransactions = [];

    public function noMoneyCollectedYet(): bool
    {
        return empty($this->paidTransactions);
    }

    public function collectMoney(string $transactionId, float $collectedAmount, Carbon $collectedAt): self
    {
        $this->recordThat(new MoneyCollected(
            $this->aggregateRootUuid(),
            $transactionId,
            $collectedAmount,
            $collectedAt,
            $this->getCollectedMoneyType($collectedAmount)->value
        ));

        return $this;
    }

    public function applyMoneyCollected(MoneyCollected $event)
    {
        $this->paidTransactions[$event->transactionId] = ['amount' => $event->amount, 'collectedAt' => $event->collectedAt];
    }

    protected function amountLessThanDailyAmount(float $amount): bool
    {
        return $amount < $this->aggregateRoot->dailyAmount;
    }

    protected function getCollectedMoneyType(float $collectedAmount): CollectedMoneyStatus
    {
        return match (true) {
            $collectedAmount == 0 => CollectedMoneyStatus::NotCollected,
            $collectedAmount == $this->aggregateRoot->dailyAmount => CollectedMoneyStatus::FullyCollected,
            $collectedAmount < $this->aggregateRoot->dailyAmount => CollectedMoneyStatus::PartiallyCollected,
            default => CollectedMoneyStatus::OverCollected,
        };
    }
}
