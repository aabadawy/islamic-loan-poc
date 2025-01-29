<?php

namespace Feature\Aggregators;

use App\Aggregators\LoanAggregateRoot;
use App\CollectedMoneyStatus;
use App\Events\LoanApproved;
use App\Events\LoanChangeAmountRequestRejected;
use App\Events\LoanPaid;
use App\Events\LoanRequested;
use App\Events\LoanRequestedAmountChanged;
use App\Events\MoneyCollected;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoanAggregatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCanRequestLoan()
    {
        $user_id = User::factory()->createOne()->id;

        $this->freezeTime();

        LoanAggregateRoot::fake($uuid = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id) {
                $aggregateRoot->requestLoan($user_id, 300, 10, now());
            })
            ->assertEventRecorded(new LoanRequested($uuid, $user_id, 300, 10, now()));
    }

    #[Test]
    public function itCanRequestAmountChangeDuringLoanStillRequested()
    {
        $user_id = User::factory()->createOne()->id;

        $this->freezeTime();

        $aggregateRoot = LoanAggregateRoot::fake($uuid = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id) {
                $aggregateRoot->requestLoan($user_id, 300, 10, now());
            })
            ->assertEventRecorded(new LoanRequested($uuid, $user_id, 300, 10, now()))
            ->when(function (LoanAggregateRoot $aggregateRoot) {
                $aggregateRoot->requestToChangeLoanAmount(300, 10);
            })->assertEventRecorded(new LoanRequestedAmountChanged($uuid, 300, 10));

        $aggregateRoot->when(function (LoanAggregateRoot $aggregateRoot) {
            $aggregateRoot
                ->approveLoan(now())
                ->requestToChangeLoanAmount(300, 10);
        })->assertEventRecorded(new LoanApproved($uuid, now()))
            ->assertEventRecorded(new LoanChangeAmountRequestRejected($uuid, 300, 10, 'trying to request loan when loan status is 2'));
    }

    #[Test]
    public function itCanApproveLoan()
    {
        $user_id = User::factory()->createOne()->id;

        $this->freezeTime();

        LoanAggregateRoot::fake($uuid = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id) {
                $aggregateRoot->requestLoan($user_id, 300, 10, now());
            })
            ->assertEventRecorded(new LoanRequested($uuid, $user_id, 300, 10, now()))
            ->when(function (LoanAggregateRoot $aggregateRoot) {
                $aggregateRoot->approveLoan(now());
            })->assertEventRecorded(new LoanApproved($uuid, now()));
    }

    #[Test]
    public function itCanCollectMoney()
    {
        $user_id = User::factory()->createOne()->id;

        $this->freezeTime();

        $loanTransactionId = Str::uuid7();

        LoanAggregateRoot::fake($uuid = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id) {
                $aggregateRoot->requestLoan($user_id, 300, 100, now());
            })
            ->assertEventRecorded(new LoanRequested($uuid, $user_id, 300, 100, now()))
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($loanTransactionId) {
                $aggregateRoot->collectMoney($loanTransactionId, 100, now()->addHours(5));
            })
            ->assertEventRecorded(new MoneyCollected($uuid, $loanTransactionId, 100, now()->addHours(5), CollectedMoneyStatus::FullyCollected->value));
    }

    #[Test]
    public function itShouldTransformLoanToPaidAfterLastTransactionCollected()
    {
        $user_id = User::factory()->createOne()->id;

        $this->freezeTime();

        $firstTransactionId = Str::uuid7();
        $lastTransactionId = Str::uuid7();

        LoanAggregateRoot::fake($uuid = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id, $firstTransactionId) {
                $aggregateRoot->requestLoan($user_id, 200, 100, now()->subDays(3))
                    ->collectMoney($firstTransactionId, 100, now()->subDays(2));
            })
            ->assertNotRecorded(LoanPaid::class)
            // after last transaction collected, then LoanPaid event should be recorded
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($lastTransactionId) {
                $aggregateRoot->collectMoney($lastTransactionId, 100, now()->addDays(1));
            })
            ->assertEventRecorded(new LoanPaid($lastTransactionId, 100, now()->addDays(1)));
    }

    #[Test]
    public function itShouldSetCollectedMoneyStatusBasedOnCollectedAmount()
    {
        $user_id = User::factory()->createOne()->id;
        $fullyCollectedId = Str::uuid7();
        $partialCollectedId = Str::uuid7();
        $overCollectedId = Str::uuid7();
        $noMoneyCollectedId = Str::uuid7();
        $lastFullCollectedId = Str::uuid7();

        $this->freezeTime();

        LoanAggregateRoot::fake($loanId = Str::uuid7())
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($user_id, $fullyCollectedId) {
                $aggregateRoot
                    ->requestLoan($user_id, 400, 100, now()->subDays(5))
                    ->collectMoney($fullyCollectedId, 100, now()->subDays(4));
            })
            ->assertEventRecorded(new MoneyCollected($loanId, $fullyCollectedId, 100, now()->subDays(4), CollectedMoneyStatus::FullyCollected->value))
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($partialCollectedId) {
                $aggregateRoot->collectMoney($partialCollectedId, 90, now()->subDays(3));
            })
            ->assertEventRecorded(new MoneyCollected($loanId, $partialCollectedId, 90, now()->subDays(3), CollectedMoneyStatus::PartiallyCollected->value))
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($overCollectedId) {
                $aggregateRoot->collectMoney($overCollectedId, 110, now()->subDays(2));
            })
            ->assertEventRecorded(new MoneyCollected($loanId, $overCollectedId, 110, now()->subDays(2), CollectedMoneyStatus::OverCollected->value))
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($noMoneyCollectedId) {
                $aggregateRoot->collectMoney($noMoneyCollectedId, 0, now()->subDays(1));
            })
            ->assertEventRecorded(new MoneyCollected($loanId, $noMoneyCollectedId, 0, now()->subDays(1), CollectedMoneyStatus::NotCollected->value))
            ->when(function (LoanAggregateRoot $aggregateRoot) use ($lastFullCollectedId) {
                $aggregateRoot->collectMoney($lastFullCollectedId, 100, now());
            })
            ->assertEventRecorded(new MoneyCollected($loanId, $lastFullCollectedId, 100, now(), CollectedMoneyStatus::FullyCollected->value))
            ->assertEventRecorded(new LoanPaid($lastFullCollectedId, 100, now()));
    }
}
