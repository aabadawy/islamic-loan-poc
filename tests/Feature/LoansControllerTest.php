<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoansControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_should_request_loan()
    {
        $authUser = User::factory()->createOne();

        $this->actingAs($authUser, 'sanctum')->post(route('loans.store'), [
            'requested_amount' => 100,
            'daily_amount' => 100,
        ])->assertSuccessful();

    }
}
