<x-mail::message>
# {{ $headline }}

Hello {{ $recipient->name }},

@if ($type === \App\Mail\ProjectTaskNotification::UNASSIGNED)
You are no longer assigned to the task **{{ $task->title }}** in project **{{ $project->name }}**.
@else
Task **{{ $task->title }}** in project **{{ $project->name }}** ({{ $project->code }}).
@endif

@if ($type !== \App\Mail\ProjectTaskNotification::UNASSIGNED)
<x-mail::table>
| | |
|:--|:--|
| Status | {{ \App\Models\ProjectTask::STATUSES[$task->status] ?? $task->status }} |
| Priority | {{ \App\Models\ProjectTask::PRIORITIES[$task->priority] ?? $task->priority }} |
| Start | {{ $task->start_date?->format('D, d M Y') ?? '—' }} |
| Due | {{ $task->due_date?->format('D, d M Y') ?? '—' }} |
</x-mail::table>

@if ($task->description)
{{ $task->description }}

@endif
@endif
@if ($changes !== [])
**What changed**

@foreach ($changes as $label => [$before, $after])
- {{ $label }}: ~~{{ $before }}~~ → **{{ $after }}**
@endforeach

@endif
<x-mail::button :url="$taskUrl">
Open project
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
