<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendStaffRecoveryWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [15, 60, 180];

    public function __construct(public string $phone, public string $code) {}

    public function handle(): void
    {
        $url = config('services.whatsapp.url');
        $token = config('services.whatsapp.token');
        if (! $url || ! $token) {
            throw new RuntimeException('WhatsApp Business API is not configured.');
        }

        Http::withToken($token)->acceptJson()->timeout(20)->post($url, [
            'messaging_product' => 'whatsapp', 'to' => $this->phone, 'type' => 'template',
            'template' => [
                'name' => config('services.whatsapp.template', 'password_reset_code'),
                'language' => ['code' => config('services.whatsapp.language', 'ar')],
                'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $this->code]]]],
            ],
        ])->throw();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Staff recovery WhatsApp failed', ['phone' => $this->phone, 'error' => $exception->getMessage()]);
    }
}
