<?php

namespace App\Http\Controllers;

use App\Aggregators\LoanAggregateRoot;
use App\Http\Requests\UpdateLoadRequest;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoansController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        LoanAggregateRoot::retrieve($uuid = Str::uuid7())
            ->requestLoan(auth()->id(), $request->float('requested_amount'), $request->float('daily_amount'), now())
            ->persist();

        return response()->json(['loan' => Loan::find($uuid)]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLoadRequest $request, Loan $loan)
    {
        LoanAggregateRoot::retrieve($loan->id)
            ->requestToChangeLoanAmount($request->float('requested_amount'), $request->float('daily_amount'))
            ->persist();

        return response()->json(['loan' => $loan->fresh()]);
    }

    public function approve(Loan $loan)
    {
        LoanAggregateRoot::retrieve($loan->id)
            ->approveLoan(now())
            ->persist();

        return response()->json(['loan' => $loan->fresh()]);
    }

    public function collectMoney(Loan $loan, Request $request)
    {
        LoanAggregateRoot::retrieve($loan->id)
            ->collectMoney(Str::uuid7(), $request->float('amount'), now())
            ->persist();

        return response()->json(['loan' => $loan->refresh()]);
    }
}
