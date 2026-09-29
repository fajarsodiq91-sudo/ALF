<?php

namespace App\Models\Concerns;

use App\Services\TransactionProof;
use Illuminate\Support\Facades\Storage;

/** Finance records that carry an uploaded proof file (proof_path) and/or a proof link (proof_url). */
trait HasProof
{
    protected static function bootHasProof(): void
    {
        static::deleted(function (self $model) {
            if ($model->proof_path) {
                Storage::disk(TransactionProof::DISK)->delete($model->proof_path);
            }
        });
    }

    public function hasProof(): bool
    {
        return $this->proof_path !== null || $this->proof_url !== null;
    }

    public function proofFileUrl(): ?string
    {
        return $this->proof_path
            ? route('finance.proofs.show', ['type' => TransactionProof::type($this), 'id' => $this->getKey()])
            : null;
    }
}
