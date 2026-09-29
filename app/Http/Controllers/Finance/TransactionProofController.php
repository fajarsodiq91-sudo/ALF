<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\TransactionProof;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionProofController extends Controller
{
    /** Opens the uploaded proof inline (images and PDFs preview in the browser). */
    public function show(string $type, int $id): StreamedResponse
    {
        $this->authorize('finance.view');

        $record = TransactionProof::TYPES[$type]::findOrFail($id);
        $disk = Storage::disk(TransactionProof::DISK);

        abort_unless($record->proof_path && $disk->exists($record->proof_path), 404);

        return $disk->response($record->proof_path, $record->proof_original_name);
    }
}
