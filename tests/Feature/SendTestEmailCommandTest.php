<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendTestEmailCommandTest extends TestCase
{
    public function test_it_refuses_a_mailer_that_only_writes_to_a_log(): void
    {
        config(['mail.default' => 'log']);
        Mail::shouldReceive('raw')->never();

        $this->artisan('mail:test', ['email' => 'me@example.com'])
            ->expectsOutputToContain('does not deliver real email')
            ->assertFailed();
    }

    public function test_it_shows_the_settings_and_reports_a_successful_send_without_leaking_the_password(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 465,
            'mail.mailers.smtp.username' => 'admin@example.com',
            'mail.mailers.smtp.password' => 'super-secret-password',
        ]);
        Mail::shouldReceive('raw')->once();

        $this->artisan('mail:test', ['email' => 'me@example.com'])
            ->expectsOutputToContain('smtp.example.com:465')
            ->doesntExpectOutputToContain('super-secret-password')
            ->expectsOutputToContain('accepted by the mail server')
            ->assertSuccessful();
    }

    public function test_it_reports_the_real_error_when_sending_fails(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('raw')->andThrow(new RuntimeException('Connection could not be established'));

        $this->artisan('mail:test', ['email' => 'me@example.com'])
            ->expectsOutputToContain('Sending failed: Connection could not be established')
            ->assertFailed();
    }
}
