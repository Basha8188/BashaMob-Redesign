<?php

namespace App\Jobs;

use App\Mail\MaintenanceRequestReceived;
use App\Models\MaintenanceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMaintenanceRequestConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 60, 180];

    public function __construct(public int $requestId) {}

    public function handle(): void
    {
        $request = MaintenanceRequest::findOrFail($this->requestId);
        Mail::to($request->email)->send(new MaintenanceRequestReceived($request));
    }

    public function failed(Throwable $e): void
    {
        Log::error('Maintenance confirmation failed', ['request_id' => $this->requestId, 'error' => $e->getMessage()]);
    }
}
