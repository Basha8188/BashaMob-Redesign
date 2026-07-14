<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
