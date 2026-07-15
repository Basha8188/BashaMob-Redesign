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

    public function test_customer_can_track_request_by_number_phone_or_exact_name(): void
    {
        $request = MaintenanceRequest::create([
            'request_number' => 'BM-2026-123456',
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '01012345678',
            'device' => 'موبايل',
            'brand' => 'Apple',
            'problem' => 'الشاشة لا تعمل',
            'status' => 'in_progress',
        ]);

        foreach ([$request->request_number, $request->phone, $request->name] as $query) {
            $this->postJson(route('maintenance.requests.track'), ['query' => $query])
                ->assertOk()
                ->assertJsonPath('requests.0.request_number', $request->request_number)
                ->assertJsonPath('requests.0.status', 'قيد الإصلاح')
                ->assertJsonMissingPath('requests.0.email')
                ->assertJsonMissingPath('requests.0.problem');
        }
    }

    public function test_tracking_returns_a_safe_empty_result_for_unknown_request(): void
    {
        $this->postJson(route('maintenance.requests.track'), ['query' => 'BM-UNKNOWN'])
            ->assertOk()
            ->assertJsonCount(0, 'requests');
    }
}
