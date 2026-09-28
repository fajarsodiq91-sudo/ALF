<?php

namespace App\Http\Requests\Sales;

use App\Models\TrainingProgram;
use App\Services\BookedSlots;
use App\Services\OperatingHours;
use App\Services\SessionPaymentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** The public form a customer fills in themselves after scanning the QR code. */
class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'programs' => ['nullable', 'array', 'max:5'],
            'programs.*.training_program_id' => ['required', Rule::exists(TrainingProgram::class, 'id')->where('is_active', true)],
            'programs.*.payment_plan' => ['nullable', Rule::in(array_keys(SessionPaymentPlan::PLANS))],
            'programs.*.meetings' => ['required', 'array', 'min:1', 'max:100'],
            'programs.*.meetings.*.meeting_date' => ['required', 'date', 'after_or_equal:today'],
            'programs.*.meetings.*.start_time' => ['nullable', 'date_format:H:i'],
            'programs.*.meetings.*.end_time' => ['nullable', 'date_format:H:i', 'after:programs.*.meetings.*.start_time'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'programs.*.training_program_id.required' => 'Please choose a program.',
            'programs.*.training_program_id.exists' => 'That program is not available.',
            'programs.*.meetings.required' => 'Pick at least one preferred date for each program.',
            'programs.*.meetings.min' => 'Pick at least one preferred date for each program.',
            'programs.*.meetings.*.meeting_date.required' => 'Please choose a date.',
            'programs.*.meetings.*.meeting_date.after_or_equal' => 'Preferred dates cannot be in the past.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $catalog = TrainingProgram::whereIn('id', collect($this->input('programs', []))->pluck('training_program_id'))->get()->keyBy('id');

            $seen = [];

            foreach ($this->input('programs', []) as $i => $program) {
                $expected = $catalog[$program['training_program_id']]->duration_days;
                $chosen = count($program['meetings']);

                if ($chosen !== $expected) {
                    $validator->errors()->add("programs.{$i}.meetings", "{$catalog[$program['training_program_id']]->name} has {$expected} meeting(s); please choose a date and time for all {$expected} (you chose {$chosen}).");

                    continue;
                }

                $slots = collect($program['meetings'])->map(fn ($m) => ($m['meeting_date'] ?? '').'|'.($m['start_time'] ?? '').'|'.($m['end_time'] ?? ''));

                if ($slots->count() !== $slots->unique()->count()) {
                    $validator->errors()->add("programs.{$i}.meetings", "{$catalog[$program['training_program_id']]->name}: the same date and time was chosen more than once.");

                    continue;
                }

                foreach ($program['meetings'] as $j => $meeting) {
                    $start = $meeting['start_time'] ?? null;
                    $end = $meeting['end_time'] ?? null;
                    $violation = OperatingHours::violation($meeting['meeting_date'], $start, $end);

                    if ($violation) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", 'Program '.($i + 1).', date '.($j + 1).': '.$violation);

                        continue;
                    }

                    if ($start && $end && BookedSlots::conflicts($meeting['meeting_date'], $start, $end)) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", BookedSlots::describe($meeting['meeting_date'], $start, $end).' has just been booked by someone else. Please choose another slot.');
                    }

                    $key = $meeting['meeting_date'].'|'.$start.'|'.$end;

                    if (isset($seen[$key])) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", 'You chose the same date and time for two meetings. Please pick different slots.');
                    }

                    $seen[$key] = true;
                }
            }
        }];
    }

    /**
     * The chosen programs reduced to what is stored: program id plus preferred dates and slots.
     *
     * @return list<array<string, mixed>>
     */
    public function requestedPrograms(): array
    {
        return collect($this->validated('programs', []))->map(fn ($program) => [
            'training_program_id' => (int) $program['training_program_id'],
            'payment_plan' => SessionPaymentPlan::effective($program['payment_plan'] ?? SessionPaymentPlan::FULL, count($program['meetings'])),
            'meetings' => collect($program['meetings'])->map(fn ($meeting) => [
                'meeting_date' => $meeting['meeting_date'],
                'start_time' => $meeting['start_time'] ?? null,
                'end_time' => $meeting['end_time'] ?? null,
            ])->values()->all(),
        ])->values()->all();
    }
}
