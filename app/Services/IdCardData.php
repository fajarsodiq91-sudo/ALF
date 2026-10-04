<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;

/** What an ID card shows for an employee or a customer, shared by the ERP and the customer portal. */
class IdCardData
{
    /** @return array<string, mixed> */
    public static function employee(Employee $employee): array
    {
        return [
            'role' => $employee->position ?: 'Karyawan',
            'accent' => '#8b0000',
            'number' => $employee->employee_number,
            'name' => $employee->name,
            'subtitle' => null,
            'photoUrl' => $employee->photoUrl(),
            'initials' => $employee->initials(),
            'qr' => QrCodeGenerator::svg(route('id-cards.verify', $employee->idCardToken()), 400),
        ];
    }

    /** @return array<string, mixed> */
    public static function customer(Customer $customer): array
    {
        $since = ($customer->approved_at ?? $customer->created_at)->locale('id')->translatedFormat('d F Y');

        return [
            'role' => 'Customer',
            'accent' => '#1e3a5f',
            'number' => $customer->customer_code,
            'name' => $customer->name,
            'subtitle' => 'Valid from '.$since,
            'photoUrl' => $customer->photoUrl(),
            'initials' => collect(preg_split('/\s+/', trim($customer->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode(''),
            'qr' => QrCodeGenerator::svg(route('id-cards.verify', $customer->idCardToken()), 400),
        ];
    }
}
