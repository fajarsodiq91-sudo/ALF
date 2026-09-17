<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransferTransactionRequest;
use App\Http\Requests\Finance\UpdateTransferTransactionRequest;
use App\Models\Transfer;

class TransferTransactionController extends Controller
{
    public function index()
    {
        $this->authorize('finance.manage');

        $transfers = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'createdBy'])
            ->latest('transfer_date')
            ->paginate(20);

        return view('erp.finance.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $this->authorize('finance.manage');

        return view('erp.finance.transfers.create');
    }

    public function store(StoreTransferTransactionRequest $request)
    {
        $transfer = Transfer::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'transfer_number' => 'TRF-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        return redirect()->route('finance.transfers')->with('status', 'Transfer recorded.');
    }

    public function edit(Transfer $transfer)
    {
        $this->authorize('finance.manage');

        return view('erp.finance.transfers.edit', compact('transfer'));
    }

    public function update(UpdateTransferTransactionRequest $request, Transfer $transfer)
    {
        $transfer->update($request->validated());

        return redirect()->route('finance.transfers')->with('status', 'Transfer updated.');
    }

    public function destroy(Transfer $transfer)
    {
        $this->authorize('finance.manage');

        $transfer->delete();

        return redirect()->route('finance.transfers')->with('status', 'Transfer deleted.');
    }
}
