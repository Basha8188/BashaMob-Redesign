<?php

namespace Tests\Feature;

use App\Mail\StaffRecoveryCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

    public function test_staff_can_request_and_verify_an_email_recovery_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'admin@bashamob.local']);

        $response = $this->postJson(route('staff.recovery.send'), [
            'method' => 'email',
            'destination' => $user->email,
        ])->assertOk()->assertJsonStructure(['message', 'challenge']);

        Mail::assertSent(StaffRecoveryCode::class, fn ($mail) => $mail->hasTo($user->email));
        $challenge = $response->json('challenge');
        $recovery = Cache::get("staff-recovery:{$challenge}");
        $recovery['code'] = Hash::make('123456');
        Cache::put("staff-recovery:{$challenge}", $recovery, now()->addMinutes(10));

        $this->postJson(route('staff.recovery.verify'), [
            'challenge' => $challenge,
            'code' => '123456',
            'password' => 'new-password-2026',
            'password_confirmation' => 'new-password-2026',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-2026', $user->fresh()->password));
    }

    public function test_staff_can_receive_a_whatsapp_recovery_code(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);
        config()->set('services.whatsapp.url', 'https://graph.example.test/messages');
        config()->set('services.whatsapp.token', 'test-token');
        User::factory()->create(['phone' => '201012345678']);

        $this->postJson(route('staff.recovery.send'), [
            'method' => 'whatsapp',
            'destination' => '01012345678',
        ])->assertOk()->assertJsonStructure(['message', 'challenge']);

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.example.test/messages'
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '201012345678');
    }
}
