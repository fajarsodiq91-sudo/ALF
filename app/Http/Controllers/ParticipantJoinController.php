<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TrainingSession;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Employees of a corporate customer join a session through its link and get their own portal login. */
class ParticipantJoinController extends Controller
{
    public function show(string $token): View
    {
        $session = $this->session($token);

        return view('participant.join', ['session' => $session, 'open' => $session->participantLinkOpen()]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $session = $this->session($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $participant = DB::transaction(function () use ($session, $data) {
                $locked = TrainingSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

                if (! $locked->participantLinkOpen()) {
                    throw new DomainException('Registration for this session is closed or already full.');
                }

                if ($locked->participants()->where('customers.email', $data['email'])->exists()) {
                    throw new DomainException('This email address has already joined this session.');
                }

                $participant = new Customer([
                    ...$data,
                    'customer_type' => $locked->customer?->customer_type ?? 'individual',
                    'is_active' => true,
                    'company_customer_id' => $locked->customer_id,
                ]);
                $participant->registration_status = Customer::REGISTRATION_COMPLETE;
                $participant->save();
                $participant->password = $participant->customer_code;
                $participant->must_change_password = true;
                $participant->save();

                $locked->participants()->attach($participant->id);
                $locked->update(['participants_count' => $locked->participants()->count()]);

                return $participant;
            });
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['name' => $exception->getMessage()]);
        }

        return redirect()->route('participant.done')->with('participant', [
            'name' => $participant->name,
            'code' => $participant->customer_code,
            'program' => $session->program->name,
        ]);
    }

    public function done(Request $request): View|RedirectResponse
    {
        $participant = $request->session()->get('participant');

        return $participant ? view('participant.done', ['participant' => $participant]) : redirect()->route('portal.login');
    }

    private function session(string $token): TrainingSession
    {
        return TrainingSession::where('participant_token', $token)->with(['program', 'customer'])->firstOrFail();
    }
}
