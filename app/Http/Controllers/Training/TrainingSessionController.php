<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingSessionRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Services\BookedSlots;
use App\Services\CertificateIssuer;
use App\Services\CustomerNotifier;
use App\Services\PaymentInvoices;
use App\Services\SessionPaymentPlan;
use App\Services\SessionStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TrainingSessionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = function ($query) use ($request) {
            $query
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->when($request->filled('program_id'), fn ($query) => $query->where('training_program_id', $request->integer('program_id')))
                ->when($request->input('payment') === 'awaiting', fn ($query) => $query->whereHas('payments', fn ($payment) => $payment->whereNull('income_transaction_id')))
                ->when($request->boolean('ready_to_complete'), fn ($query) => $query->whereNotIn('status', ['completed', 'cancelled'])->where('end_date', '<', now()))
                ->when($request->boolean('reschedule_pending'), fn ($query) => $query->whereHas('meetings.rescheduleRequests', fn ($rescheduleRequest) => $rescheduleRequest->where('status', 'pending')));
        };

        $totalFee = (float) TrainingSession::query()->tap($filters)->where('status', '!=', 'cancelled')->sum('fee');

        $sessions = TrainingSession::query()
            ->with(['program', 'customer', 'instructor'])
            ->withCount('meetings')
            ->tap($filters)
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('erp.training.sessions.index', [
            'sessions' => $sessions,
            'programs' => TrainingProgram::orderBy('name')->get(),
            'totalFee' => $totalFee,
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('erp.training.sessions.create', $this->formData());
    }

    public function store(SaveTrainingSessionRequest $request): RedirectResponse
    {
        $session = TrainingSession::create([...$request->validated(), 'status' => $request->validated('status') === 'auto' ? 'planned' : $request->validated('status'), 'payment_plan' => $request->validated('payment_plan') ?? SessionPaymentPlan::FULL]);
        SessionPaymentPlan::generate($session);
        $session->syncParticipantToken();
        CertificateIssuer::issueFor($session);
        PaymentInvoices::sendDue($session);
        CustomerNotifier::sessionScheduled($session);

        return redirect()->route('training.index')->with('status', 'Training session created successfully.');
    }

    public function edit(TrainingSession $session): View
    {
        $this->authorize('training.manage');

        return view('erp.training.sessions.edit', [...$this->formData(), 'session' => $session->load(['meetings.rescheduleRequests', 'meetings.attendances.customer', 'payments.incomeTransaction', 'participants']), 'accounts' => Account::where('is_active', true)->orderBy('name')->get(), 'booked' => BookedSlots::keys(null, true)]);
    }

    public function update(SaveTrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $data = [...$request->validated(), 'status' => SessionStatus::forSave($session, $request->validated('status')), 'payment_plan' => $request->validated('payment_plan') ?? $session->payment_plan];
        $changesMoney = (float) $data['fee'] !== (float) $session->fee || $data['payment_plan'] !== $session->payment_plan;

        if ($changesMoney && $session->payments()->whereNotNull('income_transaction_id')->exists()) {
            return back()->withInput()->withErrors(['fee' => 'A payment for this session is already recorded in Finance. Cancel it before changing the fee or payment plan.']);
        }

        $joined = $session->participants()->count();

        if (($data['participant_limit'] ?? null) !== null && $data['participant_limit'] < $joined) {
            return back()->withInput()->withErrors(['participant_limit' => "{$joined} participant(s) already joined; the limit cannot be lower."]);
        }

        $session->update($data);
        $statusChanged = $session->wasChanged('status');
        $session->syncParticipantToken();
        $certificates = CertificateIssuer::issueFor($session);

        // Sessions created before payment plans existed get their schedule the first time they are saved.
        if ($changesMoney || $session->payments()->doesntExist()) {
            SessionPaymentPlan::generate($session);
        }

        PaymentInvoices::sendDue($session);

        if ($statusChanged) {
            CustomerNotifier::sessionStatusChanged($session);
        }

        return redirect()->route('training.index')->with('status', 'Training session updated successfully.'.($certificates ? " {$certificates} certificate(s) issued." : ''));
    }

    public function destroy(TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');

        $session->delete();

        return redirect()->route('training.index')->with('status', 'Training session deleted successfully.');
    }

    /** One-click close-out once a session has run its course: marks it done and issues certificates. */
    public function complete(TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');

        if (in_array($session->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'This session is already finished.');
        }

        if ($session->meetings()->exists()) {
            return back()->with('error', 'This session completes by itself once every meeting is marked as done.');
        }

        $session->update(['status' => 'completed']);
        CustomerNotifier::sessionStatusChanged($session);
        $certificates = CertificateIssuer::issueFor($session);

        return redirect()->route('training.index')->with('status', 'Session marked as done.'.($certificates ? " {$certificates} certificate(s) issued." : ''));
    }

    /**
     * Certificates of a learning session as one printable PDF (a page per holder), available before the session is
     * completed. They carry the real number and QR code but stay hidden from the customer until completion.
     */
    public function certificates(TrainingSession $session): Response|RedirectResponse
    {
        $this->authorize('training.manage');

        $certificates = CertificateIssuer::draftFor($session);

        if ($certificates->isEmpty()) {
            return back()->with('error', $session->status === 'cancelled' || $session->program?->program_type !== 'learning'
                ? 'Certificates are only available for active learning sessions.'
                : 'There is nobody to certify yet. Add a participant first.');
        }

        $certificates->load(['session.program', 'session.meetings', 'session.instructor', 'customer']);

        return Pdf::loadView('certificates.batch-pdf', ['certificates' => $certificates, 'title' => $session->program->name.' certificates'])
            ->setPaper('a4', 'landscape')
            ->download($certificates->count() === 1
                ? $certificates->first()->downloadFilename()
                : 'certificates-'.Str::slug($session->program->name).'-'.$session->start_date->format('Ymd').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => TrainingProgram::orderBy('name')->get(),
            'customers' => Customer::registered()->orderBy('name')->get(),
            'instructors' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ];
    }
}
