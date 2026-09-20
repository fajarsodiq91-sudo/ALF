<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransferTransactionRequest;
use App\Http\Requests\Finance\UpdateTransferTransactionRequest;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class TransferTransactionController extends Controller
{
    public function index()
    {
        $this->authorize('finance.manage');

        $transfers = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'feeExpense', 'createdBy'])
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
        DB::transaction(function () use ($request) {
            $transfer = Transfer::create([
                ...$request->safe()->except('fee'),
                'created_by' => auth()->id(),
                'transfer_number' => 'TRF-' . date('YmdHis') . '-' . rand(1000, 9999),
            ]);

            $this->syncFee($transfer, (float) $request->input('fee', 0));
        });

        return redirect()->route('finance.transfers')->with('status', 'Transfer recorded.');
    }

    public function edit(Transfer $transfer)
    {
        $this->authorize('finance.manage');

        return view('erp.finance.transfers.edit', compact('transfer'));
    }

    public function update(UpdateTransferTransactionRequest $request, Transfer $transfer)
    {
        DB::transaction(function () use ($request, $transfer) {
            $transfer->update($request->safe()->except('fee'));

            $this->syncFee($transfer, (float) $request->input('fee', 0));
        });

        return redirect()->route('finance.transfers')->with('status', 'Transfer updated.');
    }

    public function destroy(Transfer $transfer)
    {
        $this->authorize('finance.manage');

        $transfer->delete();

        return redirect()->route('finance.transfers')->with('status', 'Transfer deleted.');
    }

    /** The bank admin fee is stored as an expense on the source account so balances and reports pick it up. */
    private function syncFee(Transfer $transfer, float $fee): void
    {
        $existing = $transfer->feeExpense;

        if ($fee <= 0) {
            $existing?->delete();

            return;
        }

        $category = Category::firstOrCreate(
            ['name' => 'Bank Charges', 'type' => 'expense'],
            ['is_active' => true, 'description' => 'Bank admin fees and account charges'],
        );

        $attributes = [
            'transaction_date' => $transfer->transfer_date,
            'account_id' => $transfer->from_account_id,
            'category_id' => $category->id,
            'payee' => 'Bank admin fee',
            'description' => "Admin fee for {$transfer->transfer_number}",
            'payment_method' => 'Bank Fee',
            'subtotal' => $fee,
            'tax_amount' => 0,
            'amount' => $fee,
        ];

        if ($existing) {
            $existing->update($attributes);

            return;
        }

        ExpenseTransaction::create([
            ...$attributes,
            'transfer_id' => $transfer->id,
            'transaction_number' => 'EXP-' . date('YmdHis') . '-' . rand(1000, 9999),
            'created_by' => auth()->id(),
        ]);
    }
}
