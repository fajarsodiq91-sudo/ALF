<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\IdCardData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A customer's own ID card, printable from the portal. */
class PortalIdCardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');

        abort_if($customer->customer_code === null, 404);

        return view('id-cards.show', ['cards' => [IdCardData::customer($customer)]]);
    }
}
