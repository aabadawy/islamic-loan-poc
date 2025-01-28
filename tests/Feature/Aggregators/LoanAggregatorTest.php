<?php

namespace Feature\Aggregators;

use App\Aggregators\LoanAggregateRoot;
use App\Events\LoanApproved;
use App\Events\LoanChangeAmountRequestRejected;
use App\Events\LoanRequested;
use App\Events\LoanRequestedAmountChanged;
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
}
