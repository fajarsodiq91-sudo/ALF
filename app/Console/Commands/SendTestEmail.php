<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestEmail extends Command
{
    protected $signature = 'mail:test {email : The address to send the test message to}';

    protected $description = 'Send a test email and show the mail settings in use, to check that email really goes out from this server';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $settings = config("mail.mailers.{$mailer}", []);

        $this->line("Mailer: {$mailer}");
        $this->line('From:   '.config('mail.from.name').' <'.config('mail.from.address').'>');

        if ($mailer === 'smtp') {
            $this->line("SMTP:   {$settings['host']}:{$settings['port']}, user ".($settings['username'] ?: '(none)').', '.($settings['scheme'] ?: 'automatic TLS'));
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->error("The \"{$mailer}\" mailer does not deliver real email, so nothing was sent. Set MAIL_MAILER=smtp and the other MAIL_* settings in .env, then run php artisan config:clear.");

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you can read it, email delivery works.',
                fn ($message) => $message->to($this->argument('email'))->subject('Test email | '.config('app.name')),
            );
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email accepted by the mail server for {$this->argument('email')}. Check that inbox (and the spam folder).");

        return self::SUCCESS;
    }
}
