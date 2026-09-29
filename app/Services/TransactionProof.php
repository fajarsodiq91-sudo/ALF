<?php

namespace App\Services;

use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\TaxPayment;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Proof of a finance transaction: an uploaded receipt (kept on the private disk) and/or a link to it. */
class TransactionProof
{
    public const DISK = 'local';

    /** Request fields handled here, to strip from the model's validated data. */
    public const FIELDS = ['proof', 'proof_url', 'remove_proof'];

    /** URL segment => model, used by the download route. */
    public const TYPES = [
        'income' => IncomeTransaction::class,
        'expense' => ExpenseTransaction::class,
        'transfer' => Transfer::class,
        'loan' => Loan::class,
        'repayment' => LoanRepayment::class,
        'tax-payment' => TaxPayment::class,
    ];

    public static function rules(): array
    {
        return [
            'proof' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:5120'],
            'proof_url' => ['nullable', 'url:http,https', 'max:2048'],
            'remove_proof' => ['nullable', 'boolean'],
        ];
    }

    /** Proof attributes to save on the model; a new upload or "remove" deletes the previous file. */
    public static function attributes(Request $request, Model $model): array
    {
        $attributes = ['proof_url' => $request->input('proof_url') ?: null];
        $file = $request->file('proof');

        if ($file) {
            $attributes['proof_path'] = ImageCompressor::store($file, 'finance-proofs/'.self::type($model), self::DISK);
            $attributes['proof_original_name'] = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_proof')) {
            $attributes['proof_path'] = null;
            $attributes['proof_original_name'] = null;
        } else {
            return $attributes;
        }

        if ($model->proof_path) {
            Storage::disk(self::DISK)->delete($model->proof_path);
        }

        return $attributes;
    }

    public static function type(Model|string $model): string
    {
        return array_search(is_string($model) ? $model : $model::class, self::TYPES, true);
    }
}
