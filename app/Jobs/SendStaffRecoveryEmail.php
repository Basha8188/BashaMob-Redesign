<?php

namespace App\Jobs;

use App\Mail\StaffRecoveryCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendStaffRecoveryEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 60, 180];

    public function __construct(public string $email, public string $code) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new StaffRecoveryCode($this->code));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Staff recovery email failed', ['email' => $this->email, 'error' => $exception->getMessage()]);
    }
}
