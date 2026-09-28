<?php

namespace App\Console\Commands;

use App\Services\PaymentInvoices;
use Illuminate\Console\Command;

class SendPaymentInvoices extends Command
{
    protected $signature = 'payments:send-invoices';

    protected $description = 'Email the customers an invoice for every training payment that is now due';

    public function handle(): int
    {
        $this->info(PaymentInvoices::sendAllDue().' invoice(s) sent.');

        return self::SUCCESS;
    }
}
