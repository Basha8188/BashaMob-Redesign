<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('demo', 'testing')) {
            throw new \RuntimeException('Demo data may only be seeded in demo/testing.');
        }

        $this->call(DatabaseSeeder::class);
        Customer::firstOrCreate(['phone' => '01000000001'], ['name' => 'عميل تجريبي', 'email' => 'customer@example.test']);
        Supplier::firstOrCreate(['phone' => '01000000002'], ['name' => 'مورد تجريبي']);
        $warehouse = Warehouse::where('code', 'MAIN')->firstOrFail();
        foreach ([['DEMO-CABLE', 'كابل شحن USB-C', 60, 120], ['DEMO-CHARGER', 'شاحن سريع', 200, 350], ['DEMO-HEADSET', 'سماعة لاسلكية', 400, 650]] as [$sku, $name, $cost, $price]) {
            $product = Product::firstOrCreate(['sku' => $sku], ['name' => $name, 'tracking_type' => 'quantity', 'purchase_price' => $cost, 'sale_price' => $price, 'is_active' => true]);
            if ($product->wasRecentlyCreated) {
                app(InventoryService::class)->adjust($warehouse, $product, 20, StockMovementType::OpeningBalance, unitCost: $cost, notes: 'رصيد تجريبي');
            }
        }
    }
}
