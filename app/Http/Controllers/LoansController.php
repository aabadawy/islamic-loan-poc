<?php

namespace App\Http\Controllers;

use App\Aggregators\LoanAggregateRoot;
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
    public function update(Request $request, Loan $loan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        //
    }
}
