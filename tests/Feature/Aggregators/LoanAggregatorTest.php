<?php

namespace Feature\Aggregators;

use App\Aggregators\LoanAggregateRoot;
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
            ->assertEventRecorded(new MoneyCollected($uuid, $loanTransactionId, 100, now()->addHours(5)));
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
}
