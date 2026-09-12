<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_receive_the_liquid_cosmos_theme_by_default(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertSame('liquid-cosmos', $user->theme_name);
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

    public function test_liquid_and_legacy_theme_preferences_are_accepted(): void
    {
        $user = User::factory()->create();

        foreach (['liquid-cosmos', 'lavender', 'plum'] as $theme) {
            $this->actingAs($user)->postJson(route('theme-preference.store'), [
                'theme_name' => $theme,
                'theme_mode' => 'dark',
                'use_system_mode' => false,
            ])->assertOk()->assertJsonPath('theme.theme_name', $theme);
        }
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

    public function test_public_pages_use_one_fixed_theme_without_selection_controls(): void
    {
        foreach ([route('home'), route('shop'), route('maintenance'), route('computers'), route('staff.login')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee("root.dataset.themeName = 'premium-glass'", false)
                ->assertSee('images/theme/main-bg.png', false)
                ->assertDontSee('class="theme-picker', false)
                ->assertDontSee('class="theme-toggle', false)
                ->assertDontSee('theme-preference-url', false);
        }
    }

    public function test_saved_staff_preferences_do_not_change_the_fixed_identity(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $staff = User::factory()->create([
            'theme_name' => 'forest-moss',
            'theme_mode' => 'light',
            'use_system_mode' => true,
        ]);
        $staff->assignRole('Owner');
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk()
            ->assertSee("root.dataset.themeName = 'premium-glass'", false)
            ->assertSee("root.dataset.theme = 'unified'", false)
            ->assertDontSee('class="theme-picker', false)
            ->assertDontSee('topbar-theme theme-toggle', false)
            ->assertDontSee('theme-preference-url', false);
        $this->assertSame('forest-moss', $staff->fresh()->theme_name);
    }

    public function test_unified_theme_layer_is_loaded_last_and_covers_the_complete_interface(): void
    {
        $index = file_get_contents(resource_path('css/theme/index.css'));
        $unified = file_get_contents(resource_path('css/theme/system-unified.css'));

        $this->assertStringContainsString("@import './system-unified.css';", $index);
        $this->assertGreaterThan(
            strpos($index, "@import './system-unified.css';"),
            strpos($index, "@import './premium-glass.css';")
        );

        foreach ([
            '.admin-sidebar.glass-panel',
            '.admin-topbar.glass-panel',
            '.reference-stats',
            '.admin-table',
            '.system-search-results',
            '.ai-assistant-panel',
            'body.storefront-body',
            'body.auth-body',
            '.public-card',
        ] as $component) {
            $this->assertStringContainsString($component, $unified);
        }

        $this->assertStringContainsString('var(--theme-primary)', $unified);
        $this->assertStringContainsString('var(--theme-surface', $unified);
        $this->assertStringContainsString('backdrop-filter:none!important', $unified);
    }
}
