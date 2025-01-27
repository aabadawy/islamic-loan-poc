<?php

namespace Feature\Aggregators;

use App\Aggregators\LoanAggregateRoot;
use App\Events\LoanRequested;
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
}
