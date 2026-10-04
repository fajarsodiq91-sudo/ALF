<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Services\IdCardData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdCardController extends Controller
{
    /** Most cards one bulk print will include, to keep the page light. */
    private const BULK_LIMIT = 200;

    public function employee(Employee $employee): View
    {
        return view('id-cards.show', ['cards' => [IdCardData::employee($employee)]]);
    }

    public function employees(Request $request): View
    {
        $employees = Employee::query()->filtered($request)->orderBy('name')->limit(self::BULK_LIMIT)->get();

        return view('id-cards.show', ['cards' => $employees->map(fn (Employee $employee) => IdCardData::employee($employee))->all()]);
    }

    public function customer(Customer $customer): View
    {
        // Customers still waiting on registration have no permanent ID yet.
        abort_if($customer->customer_code === null, 404);

        return view('id-cards.show', ['cards' => [IdCardData::customer($customer)]]);
    }

    public function customers(Request $request): View
    {
        $customers = Customer::query()->filtered($request)->registered()->whereNotNull('customer_code')
            ->orderBy('name')->limit(self::BULK_LIMIT)->get();

        return view('id-cards.show', ['cards' => $customers->map(fn (Customer $customer) => IdCardData::customer($customer))->all()]);
    }
}
