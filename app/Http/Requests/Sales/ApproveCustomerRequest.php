<?php

namespace App\Http\Requests\Sales;

use App\Models\TrainingProgram;
use App\Services\BookedSlots;
use App\Services\MasterData;
use App\Services\OperatingHours;
use App\Services\SessionPaymentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApproveCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sales.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'programs' => ['required', 'array', 'min:1'],
            'programs.*.training_program_id' => ['required', Rule::exists(TrainingProgram::class, 'id')->where('is_active', true)],
            'programs.*.delivery_mode' => ['required', Rule::in(MasterData::codes('delivery_mode'))],
            'programs.*.instructor_id' => ['nullable', Rule::exists('employees', 'id')->where(fn ($query) => $query->where('status', '!=', 'resigned'))],
            'programs.*.participant_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'programs.*.location' => ['nullable', 'string', 'max:255'],
            'programs.*.payment_plan' => ['nullable', Rule::in(array_keys(SessionPaymentPlan::PLANS))],
            'programs.*.fee' => ['nullable', 'numeric', 'min:0'],
            'programs.*.meetings' => ['required', 'array', 'min:1'],
            'programs.*.meetings.*.meeting_date' => ['required', 'date'],
            'programs.*.meetings.*.start_time' => ['nullable', 'date_format:H:i'],
            'programs.*.meetings.*.end_time' => ['nullable', 'date_format:H:i', 'after:programs.*.meetings.*.start_time'],
            'programs.*.meetings.*.location' => ['nullable', 'string', 'max:255'],
            'programs.*.meetings.*.topic' => ['nullable', 'string', 'max:255'],
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

            $seen = [];
            $customerId = $this->route('customer')?->id;

            $catalog = TrainingProgram::whereIn('id', collect($this->input('programs', []))->pluck('training_program_id'))->get()->keyBy('id');

            foreach ($this->input('programs', []) as $i => $program) {
                foreach ($program['meetings'] ?? [] as $j => $meeting) {
                    $start = $meeting['start_time'] ?? null;
                    $end = $meeting['end_time'] ?? null;
                    $label = 'Meeting '.($j + 1).' of program '.($i + 1).': ';
                    $catalogProgram = $catalog[$program['training_program_id']] ?? null;
                    $violation = OperatingHours::violation($meeting['meeting_date'], $start, $end, $catalogProgram?->session_minutes, (bool) $catalogProgram?->is_corporate);

                    if ($violation) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", $label.$violation);

                        continue;
                    }

                    if ($start && $end && BookedSlots::conflicts($meeting['meeting_date'], $start, $end, $customerId)) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", $label.BookedSlots::describe($meeting['meeting_date'], $start, $end).' is already booked by another customer.');
                    }

                    $key = $meeting['meeting_date'].'|'.$start.'|'.$end;

                    if ($start && $end && isset($seen[$key])) {
                        $validator->errors()->add("programs.{$i}.meetings.{$j}.meeting_date", $label.'the same date and time is used by another meeting in this request.');
                    }

                    $seen[$key] = true;
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'programs.required' => 'Add at least one program before approving.',
            'programs.min' => 'Add at least one program before approving.',
            'programs.*.meetings.required' => 'Each program needs at least one meeting date.',
            'programs.*.meetings.min' => 'Each program needs at least one meeting date.',
        ];
    }
}
