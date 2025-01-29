<?php

namespace App\Aggregators;

use App\Events\LoanApproved;
use App\Events\LoanChangeAmountRequestRejected;
use App\Events\LoanPaid;
use App\Events\LoanRequested;
use App\Events\LoanRequestedAmountChanged;
use App\Events\MoneyCollected;
use App\LoanStatus;
use Carbon\Carbon;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class LoanAggregateRoot extends AggregateRoot
{
    protected float $requestedAmount = 0;

    public float $dailyAmount = 0;

    protected float $remainingAmount = 0;

    protected LoanStatus $status;

    protected LoanCollectedTransactions $collectedTransactions;

    public function __construct()
    {
        $this->collectedTransactions = new LoanCollectedTransactions($this);
    }

    public function requestLoan(int $userId, float $requestedAmount, float $dailyAmount, Carbon $date)
    {
        $this->recordThat(new LoanRequested($this->uuid(), $userId, $requestedAmount, $dailyAmount, $date));

        return $this;
    }

    public function requestToChangeLoanAmount(float $requestedAmount, float $dailyAmount)
    {
        if ($this->loanProcessStarted()) {
            $this->recordThat(
                new LoanChangeAmountRequestRejected(
                    $this->uuid(),
                    $requestedAmount,
                    $dailyAmount,
                    'trying to request loan when loan status is '.$this->status->value
                )
            );

            return $this;
        }

        $this->recordThat(new LoanRequestedAmountChanged($this->uuid(), $requestedAmount, $dailyAmount));

        return $this;
    }

    public function approveLoan(Carbon $approvedAt): self
    {
        $this->recordThat(new LoanApproved($this->uuid(), $approvedAt));

        return $this;
    }

    public function collectMoney(string $transactionId, float $amount, Carbon $collectedAt): self
    {
        if ($this->loanAlreadyPaid()) {
            return $this;
        }

        $this->collectedTransactions->collectMoney($transactionId, $amount, $collectedAt);

        if ($this->remainingAmount <= 0) {
            $this->recordThat(new LoanPaid($transactionId, $amount, $collectedAt));
        }

        return $this;
    }

    public function applyLoanRequested(LoanRequested $event)
    {
        $this->requestedAmount = $event->requestedAmount;

        $this->remainingAmount = $event->requestedAmount;

        $this->dailyAmount = $event->dailyAmount;

        $this->status = LoanStatus::Requested;
    }

    public function applyLoanRequestedAmountChanged(LoanRequested $event)
    {
        $this->requestedAmount = $event->requestedAmount;

        $this->remainingAmount = $event->requestedAmount;

        $this->dailyAmount = $event->dailyAmount;

        $this->status = LoanStatus::Requested;
    }

    public function applyLoanApproved(LoanApproved $event)
    {
        $this->status = LoanStatus::Approved;
    }

    public function applyMoneyCollected(MoneyCollected $event)
    {
        $this->remainingAmount -= $event->amount;

        $this->status = LoanStatus::Partial_Paid;
    }

    public function applyLoanPaid(LoanPaid $event)
    {
        $this->status = LoanStatus::Paid;
    }

    private function loanProcessStarted(): bool
    {
        return $this->status != LoanStatus::Requested;
    }

    private function loanAlreadyPaid(): bool
    {
        return $this->status == LoanStatus::Paid;
    }
}
