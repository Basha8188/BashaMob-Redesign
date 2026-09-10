<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_usable_and_does_not_duplicate_stock(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertDatabaseHas('warehouse_stocks', ['quantity' => 20]);
    }

    public function test_demo_seed_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        app(DemoSeeder::class)->run();
    }

    public function test_reseeding_preserves_changed_owner_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', env('OWNER_EMAIL', 'admin@bashamob.local'))->firstOrFail();
        $owner->update(['password' => Hash::make('Changed!Password2026')]);
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Hash::check('Changed!Password2026', $owner->fresh()->password));
    }
}
