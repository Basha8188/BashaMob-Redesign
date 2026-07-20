<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\RepairTicket;
use App\Models\Sale;
use App\Models\WarehouseStock;
use App\Services\InventoryValuationService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InventoryValuationService $inventoryValue): View
    {
        $today = Sale::whereDate('created_at', today())->where('status', 'finalized');
        $sales = (float) (clone $today)->sum('total');
        $invoices = (clone $today)->count();

        $salesTrend = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = today()->subDays($daysAgo);

            return [
                'day' => $date->translatedFormat('D'),
                'total' => (float) Sale::whereDate('created_at', $date)->where('status', 'finalized')->sum('total'),
            ];
        });
        $trendMaximum = max(1, (float) $salesTrend->max('total'));
        $salesTrend = $salesTrend->map(fn (array $point): array => $point + [
            'height' => max(8, (int) round(($point['total'] / $trendMaximum) * 100)),
        ]);

        $recentSales = Sale::with('customer')->latest()->limit(4)->get();
        $lowStock = WarehouseStock::with('product')->latest()->limit(40)->get()
            ->filter(fn (WarehouseStock $stock): bool => $stock->product && (float) $stock->quantity <= (float) $stock->product->reorder_level)
            ->take(4);
        $repairStages = collect([
            'received' => 'تم الاستلام',
            'diagnosing' => 'قيد الفحص',
            'repairing' => 'قيد الإصلاح',
            'ready_for_delivery' => 'جاهز للتسليم',
        ])->map(fn (string $label, string $status): array => [
            'label' => $label,
            'count' => RepairTicket::where('status', $status)->count(),
        ]);

        return view('admin.dashboard', [
            'metrics' => ['sales_today' => $sales, 'invoices_today' => $invoices, 'average_invoice' => $invoices ? $sales / $invoices : 0, 'purchases' => (float) Purchase::whereMonth('created_at', now()->month)->sum('total'), 'repairs' => RepairTicket::whereNotIn('status', ['delivered', 'cancelled'])->count(), 'stock_value' => $inventoryValue->total(), 'due' => (float) Sale::where('status', 'finalized')->sum('due_amount'), 'due_count' => Sale::where('status', 'finalized')->where('due_amount', '>', 0)->count(), 'returns_today' => (int) \DB::table('sale_returns')->whereDate('created_at', today())->count(), 'ready_repairs' => RepairTicket::where('status', 'ready_for_delivery')->count(), 'pending_purchases' => Purchase::whereIn('status', ['draft', 'ordered', 'partially_received'])->count()],
            'salesTrend' => $salesTrend,
            'recentSales' => $recentSales,
            'lowStock' => $lowStock,
            'repairStages' => $repairStages,
        ]);
    }
}
