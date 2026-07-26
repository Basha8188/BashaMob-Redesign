<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetEntryPointTest extends TestCase
{
    public function test_storefront_layout_uses_storefront_assets_without_admin_javascript(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('resources/js/storefront.js', $layout);
        $this->assertStringNotContainsString('resources/js/admin.js', $layout);
        $this->assertStringNotContainsString('resources/js/auth.js', $layout);
    }

    public function test_auth_layout_uses_lean_auth_assets_without_global_app_css_or_admin_javascript(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/auth.blade.php'));

        $this->assertStringContainsString('resources/js/auth.js', $layout);
        $this->assertStringContainsString('resources/css/auth-theme.css', $layout);
        $this->assertStringContainsString('resources/css/assistant.css', $layout);
        $this->assertStringNotContainsString('resources/css/app.css', $layout);
        $this->assertStringNotContainsString('resources/js/admin.js', $layout);
    }

    public function test_admin_layout_uses_admin_entry_point(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString('resources/js/admin.js', $layout);
        $this->assertStringNotContainsString('resources/js/storefront.js', $layout);
        $this->assertStringNotContainsString('resources/js/auth.js', $layout);
    }
}
