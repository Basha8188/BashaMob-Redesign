<?php

namespace Tests\Feature;

use App\Jobs\SendMaintenanceRequestConfirmation;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_request_is_saved_and_confirmation_email_is_sent(): void
    {
        Queue::fake();

        $response = $this->postJson(route('maintenance.requests.store'), [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '01012345678',
            'device' => 'موبايل',
            'brand' => 'Apple',
            'problem' => 'الشاشة لا تعمل بصورة صحيحة',
        ]);

        $response->assertCreated()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonStructure(['message', 'request_number']);

        $request = MaintenanceRequest::firstOrFail();
        $this->assertSame('pending_review', $request->status);
        $this->assertSame('ahmed@example.com', $request->email);
        Queue::assertPushed(SendMaintenanceRequestConfirmation::class, fn ($job) => $job->requestId === $request->id);
    }

    public function test_customer_request_requires_valid_contact_and_device_data(): void
    {
        Queue::fake();

        $this->postJson(route('maintenance.requests.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => '123',
            'device' => 'غير معروف',
            'brand' => '',
            'problem' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone', 'device', 'brand', 'problem']);

        $this->assertDatabaseCount('maintenance_requests', 0);
        Queue::assertNothingPushed();
    }
}
