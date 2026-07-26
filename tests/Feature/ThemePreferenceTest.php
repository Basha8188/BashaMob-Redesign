<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_and_new_users_receive_the_original_violet_theme_by_default(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertSame('default-violet', $user->theme_name);
        $this->assertSame('dark', $user->theme_mode);
        $this->assertFalse($user->use_system_mode);
    }

    public function test_authenticated_user_can_save_a_theme_independently_from_light_dark_mode(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('theme-preference.store'), [
            'theme_name' => 'forest-moss',
            'theme_mode' => 'light',
            'use_system_mode' => false,
        ])->assertOk()->assertJsonPath('theme.theme_name', 'forest-moss');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_name' => 'forest-moss',
            'theme_mode' => 'light',
            'use_system_mode' => false,
        ]);
    }

    public function test_system_mode_is_saved_and_invalid_themes_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('theme-preference.store'), [
            'theme_name' => 'moonstone',
            'theme_mode' => 'system',
            'use_system_mode' => false,
        ])->assertOk();

        $this->assertTrue($user->fresh()->use_system_mode);

        $this->actingAs($user)->postJson(route('theme-preference.store'), [
            'theme_name' => 'unknown-theme',
            'theme_mode' => 'dark',
            'use_system_mode' => false,
        ])->assertUnprocessable();

        $this->assertSame('moonstone', $user->fresh()->theme_name);
    }

    public function test_login_page_contains_all_five_visual_theme_options(): void
    {
        $response = $this->get(route('staff.login'))->assertOk();

        foreach (['default-violet', 'forest-moss', 'cocoa-dust', 'twilight-blue', 'moonstone'] as $theme) {
            $response->assertSee('data-palette="'.$theme.'"', false);
        }
    }
}
