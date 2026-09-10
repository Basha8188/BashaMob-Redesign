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

    public function test_login_page_contains_the_liquid_theme_and_all_preserved_theme_options(): void
    {
        $response = $this->get(route('staff.login'))->assertOk();

        foreach (['liquid-cosmos', 'default-violet', 'lavender', 'plum', 'forest-moss', 'cocoa-dust', 'twilight-blue', 'moonstone'] as $theme) {
            $response->assertSee('data-palette="'.$theme.'"', false);
        }
    }

    public function test_palette_picker_is_available_on_storefront_login_and_staff_screens(): void
    {
        $themes = ['liquid-cosmos', 'default-violet', 'lavender', 'plum', 'forest-moss', 'cocoa-dust', 'twilight-blue', 'moonstone'];

        foreach ([route('home'), route('staff.login')] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertSee('class="theme-picker', false);
            foreach ($themes as $theme) {
                $response->assertSee('data-palette="'.$theme.'"', false);
            }
        }

        $this->seed(RolePermissionSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('Owner');
        $response = $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk();
        $response->assertSee('admin-palette-picker', false);
        foreach ($themes as $theme) {
            $response->assertSee('data-palette="'.$theme.'"', false);
        }
    }

    public function test_palette_picker_has_a_clickable_layer_and_delegated_interaction_contract(): void
    {
        $manager = file_get_contents(resource_path('js/theme-manager.js'));
        $styles = file_get_contents(resource_path('css/theme/components.css'));

        $this->assertStringContainsString('.theme-palette button[data-palette]', $manager);
        $this->assertStringContainsString("document.addEventListener('click'", $manager);
        $this->assertStringContainsString("root.classList.toggle('theme-picker-open'", $manager);
        $this->assertStringContainsString('.theme-picker.is-open .theme-palette', $styles);
        $this->assertStringContainsString('pointer-events:auto!important', $styles);
        $this->assertStringContainsString('.admin-body .topbar-actions>.theme-picker', $styles);
    }

    public function test_unified_theme_layer_is_loaded_last_and_covers_the_complete_interface(): void
    {
        $index = file_get_contents(resource_path('css/theme/index.css'));
        $unified = file_get_contents(resource_path('css/theme/system-unified.css'));

        $this->assertStringContainsString("@import './system-unified.css';", $index);
        $this->assertGreaterThan(
            strpos($index, "@import './components.css';"),
            strpos($index, "@import './system-unified.css';")
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
