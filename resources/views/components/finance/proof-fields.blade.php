@props(['record' => null])

{{-- Proof of the transaction: upload a receipt and/or paste a link. The form needs enctype="multipart/form-data". --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-2 gap-5']) }}>
    <div>
        <label for="proof" class="block text-sm font-medium text-gray-700">Proof file</label>
        <input type="file" name="proof" id="proof" accept=".pdf,.png,.jpg,.jpeg,.webp"
               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
        <p class="mt-1 text-xs text-gray-500">Receipt, invoice or transfer slip — PDF or image, max 5 MB.</p>
        @if ($record?->proof_path)
            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                <a href="{{ $record->proofFileUrl() }}" target="_blank" class="font-medium text-brand hover:text-brand-dark">
                    Current: {{ $record->proof_original_name ?: 'view file' }}
                </a>
                <label class="inline-flex items-center gap-1 text-gray-500">
                    <input type="checkbox" name="remove_proof" value="1" @checked(old('remove_proof')) class="rounded border-gray-300 text-brand focus:ring-brand">
                    Remove
                </label>
            </div>
            <p class="mt-1 text-xs text-gray-400">Uploading a new file replaces the current one.</p>
        @endif
        @error('proof') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="proof_url" class="block text-sm font-medium text-gray-700">Proof link</label>
        <input type="url" name="proof_url" id="proof_url" value="{{ old('proof_url', $record?->proof_url) }}"
               placeholder="https://drive.google.com/…"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Optional link to the transaction (e.g. Google Drive, bank statement).</p>
        @error('proof_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
