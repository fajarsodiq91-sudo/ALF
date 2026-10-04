<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Public, read-only check of an ID card — reached by scanning the QR code printed on its back. */
class IdCardVerificationController extends Controller
{
    public function show(string $token): View|Response
    {
        if ($employee = Employee::where('id_card_token', $token)->first()) {
            return view('id-cards.verify', [
                'valid' => $employee->status !== 'resigned',
                'role' => 'Karyawan',
                'number' => $employee->employee_number,
                'name' => $employee->name,
                'subtitle' => $employee->position,
                'photoUrl' => $employee->photoUrl(),
                'initials' => $employee->initials(),
                'statusLabel' => Employee::STATUSES[$employee->status] ?? $employee->status,
            ]);
        }

        if ($customer = Customer::where('id_card_token', $token)->first()) {
            $since = ($customer->approved_at ?? $customer->created_at)->locale('id')->translatedFormat('d F Y');

            return view('id-cards.verify', [
                'valid' => $customer->is_active && $customer->registration_status === Customer::REGISTRATION_COMPLETE,
                'role' => 'Customer',
                'number' => $customer->customer_code,
                'name' => $customer->name,
                'subtitle' => 'Valid from '.$since,
                'photoUrl' => $customer->photoUrl(),
                'initials' => collect(preg_split('/\s+/', trim($customer->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode(''),
                'statusLabel' => $customer->is_active ? 'Aktif' : 'Nonaktif',
            ]);
        }

        return response()->view('id-cards.verify-invalid', [], 404);
    }
}
