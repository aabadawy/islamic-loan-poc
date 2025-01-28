<?php

namespace Tests\Feature;

use App\Aggregators\LoanAggregateRoot;
use App\LoanStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoansControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_request_loan()
    {
        $authUser = User::factory()->createOne();

        $this->actingAs($authUser, 'sanctum')->post(route('loans.store'), [
            'requested_amount' => 100,
            'daily_amount' => 100,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_can_change_loan_requested_amounts()
    {
        $authUser = User::factory()->createOne();

        LoanAggregateRoot::retrieve($loanId = Str::uuid7())
            ->requestLoan($authUser->id, 300, 10, now())
            ->persist();

        $this->actingAs($authUser, 'sanctum')->put(route('loans.update', $loanId), [
            'requested_amount' => 1000_0,
            'daily_amount' => 100,
        ])->assertSuccessful()
            ->assertJson([
                'loan' => [
                    'requested_amount' => 1000_0,
                    'daily_amount' => 100,
                ],
            ]);
    }

    #[Test]
    public function it_can_approve_loan()
    {
        $authUser = User::factory()->createOne();

        LoanAggregateRoot::retrieve($loanId = Str::uuid7())
            ->requestLoan($authUser->id, 300, 10, now())
            ->persist();

        $this->actingAs($authUser, 'sanctum')->post(route('loans.approve', $loanId))->assertSuccessful()
            ->assertJson([
                'loan' => [
                    'requested_amount' => 300,
                    'daily_amount' => 10,
                    'status' => LoanStatus::Approved->value,
                ],
            ]);
    }

    #[Test]
    public function itCanCollectLoanTransactions()
    {
        $authUser = User::factory()->createOne();

        LoanAggregateRoot::retrieve($loanId = Str::uuid7())
            ->requestLoan($authUser->id, 30, 10, now())
            ->persist();

        $this->actingAs($authUser, 'sanctum')->post(route('loans.collect-money', $loanId), ['amount' => 10])
            ->assertSuccessful()
            ->assertJson([
                'loan' => [
                    'remaining_amount' => 20,
                    'status' => LoanStatus::Partial_Paid->value,
                ],
            ]);

        $this->assertDatabaseCount('loan_transactions', 1);

        $this->actingAs($authUser, 'sanctum')->post(route('loans.collect-money', $loanId), ['amount' => 10])
            ->assertSuccessful()
            ->assertJson([
                'loan' => [
                    'remaining_amount' => 10,
                    'status' => LoanStatus::Partial_Paid->value,
                ],
            ]);

        $this->assertDatabaseCount('loan_transactions', 2);
    }
}
