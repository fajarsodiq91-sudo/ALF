@props(['record'])

@if ($record->hasProof())
    <span class="inline-flex items-center gap-2 whitespace-nowrap text-xs">
        @if ($record->proof_path)
            <a href="{{ $record->proofFileUrl() }}" target="_blank" title="{{ $record->proof_original_name }}"
               class="rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-700 hover:bg-gray-200">File</a>
        @endif
        @if ($record->proof_url)
            <a href="{{ $record->proof_url }}" target="_blank" rel="noopener noreferrer" title="{{ $record->proof_url }}"
               class="rounded bg-blue-50 px-2 py-0.5 font-medium text-blue-700 hover:bg-blue-100">Link</a>
        @endif
    </span>
@else
    <span class="text-gray-300">—</span>
@endif
