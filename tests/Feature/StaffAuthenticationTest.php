<?php

namespace Tests\Feature;

use App\Jobs\SendStaffRecoveryEmail;
use App\Jobs\SendStaffRecoveryWhatsapp;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_to_staff_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('staff.login'));
    }

    public function test_staff_member_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['email' => 'admin@bashamob.local']);

        $this->post(route('staff.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'admin@bashamob.local']);

        $this->from(route('staff.login'))->post(route('staff.login.store'), [
            'email' => 'admin@bashamob.local',
            'password' => 'incorrect-password',
        ])->assertRedirect(route('staff.login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_non_staff_user_cannot_enter_admin(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('staff.login'));
        $this->assertGuest();
    }

    public function test_inactive_staff_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('staff.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cashier_cannot_open_settings(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = User::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier)->get(route('admin.module', 'settings'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.module', 'sales'))->assertOk();
    }

    public function test_staff_can_request_and_verify_an_email_recovery_code(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email' => 'admin@bashamob.local']);

        $response = $this->postJson(route('staff.recovery.send'), [
            'method' => 'email',
            'destination' => $user->email,
        ])->assertOk()->assertJsonStructure(['message', 'challenge']);

        Queue::assertPushed(SendStaffRecoveryEmail::class, fn ($job) => $job->email === $user->email);
        $challenge = $response->json('challenge');
        $recovery = Cache::get("staff-recovery:{$challenge}");
        $recovery['code'] = Hash::make('123456');
        Cache::put("staff-recovery:{$challenge}", $recovery, now()->addMinutes(10));

        $verified = $this->postJson(route('staff.recovery.verify'), [
            'challenge' => $challenge,
            'code' => '123456',
        ])->assertOk()->assertJsonStructure(['reset_token']);

        $reset = ['reset_token' => $verified->json('reset_token'), 'password' => 'New-password!2026', 'password_confirmation' => 'New-password!2026'];
        $this->postJson(route('staff.recovery.reset'), $reset)->assertOk();
        $this->postJson(route('staff.recovery.reset'), $reset)->assertUnprocessable();

        $this->assertTrue(Hash::check('New-password!2026', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    public function test_staff_can_receive_a_whatsapp_recovery_code(): void
    {
        Queue::fake();
        User::factory()->create(['phone' => '201012345678']);

        $this->postJson(route('staff.recovery.send'), [
            'method' => 'whatsapp',
            'destination' => '01012345678',
        ])->assertOk()->assertJsonStructure(['message', 'challenge']);

        Queue::assertPushed(SendStaffRecoveryWhatsapp::class, fn ($job) => $job->phone === '201012345678');
    }

    public function test_recovery_response_does_not_reveal_if_account_exists(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $existing = $this->postJson(route('staff.recovery.send'), ['method' => 'email', 'destination' => $user->email])->assertOk();
        $missing = $this->postJson(route('staff.recovery.send'), ['method' => 'email', 'destination' => 'missing@example.com'])->assertOk();

        $this->assertSame($existing->json('message'), $missing->json('message'));
        $this->assertNotEmpty($existing->json('challenge'));
        $this->assertNotEmpty($missing->json('challenge'));
    }

    public function test_recovery_otp_is_invalidated_after_five_failed_attempts(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $response = $this->postJson(route('staff.recovery.send'), ['method' => 'email', 'destination' => $user->email]);
        $challenge = $response->json('challenge');
        $recovery = Cache::get("staff-recovery:{$challenge}");
        $recovery['code'] = Hash::make('123456');
        Cache::put("staff-recovery:{$challenge}", $recovery, now()->addMinutes(10));

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('staff.recovery.verify'), ['challenge' => $challenge, 'code' => '000000'])->assertUnprocessable();
        }

        $this->postJson(route('staff.recovery.verify'), ['challenge' => $challenge, 'code' => '123456'])->assertUnprocessable();
    }
}
