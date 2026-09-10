<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production') && (! env('OWNER_EMAIL') || strlen((string) env('OWNER_PASSWORD')) < 12 || env('OWNER_PASSWORD') === 'BashaMob!2026Local')) {
            throw new \RuntimeException('Set OWNER_EMAIL and a unique OWNER_PASSWORD of at least 12 characters before production seeding.');
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(IncentivePermissionSeeder::class);

        $owner = User::firstOrCreate(['email' => env('OWNER_EMAIL', 'admin@bashamob.local')], [
            'name' => 'BashaMob Owner',
            'phone' => env('STAFF_PHONE', '201012345678'),
            'password' => Hash::make(env('OWNER_PASSWORD', 'BashaMob!2026Local')),
            'is_staff' => true,
            'is_active' => true,
            'must_change_password' => app()->environment('production'),
            'password_changed_at' => now(),
        ]);
        $owner->syncRoles('Owner');

        $warehouse = Warehouse::firstOrCreate(['code' => 'MAIN'], ['name' => 'المخزن الرئيسي', 'is_default' => true, 'is_active' => true]);
        DB::table('cash_registers')->updateOrInsert(['code' => 'MAIN-CASH'], ['name' => 'الخزنة الرئيسية', 'warehouse_id' => $warehouse->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('expense_categories')->updateOrInsert(['name' => 'مصروفات تشغيل'], ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['currency' => 'EGP', 'store_name' => 'BashaMob', 'prevent_negative_stock' => '1'] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['group' => 'general', 'value' => $value, 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]);
        }
    }
}
