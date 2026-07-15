<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\RepairTicket;
use App\Models\Sale;
use App\Models\WarehouseStock;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Sale::whereDate('created_at', today())->where('status', 'finalized');
        $sales = (float) (clone $today)->sum('total');
        $invoices = (clone $today)->count();

        return view('admin.dashboard', ['metrics' => ['sales_today' => $sales, 'invoices_today' => $invoices, 'average_invoice' => $invoices ? $sales / $invoices : 0, 'purchases' => (float) Purchase::whereMonth('created_at', now()->month)->sum('total'), 'repairs' => RepairTicket::whereNotIn('status', ['delivered', 'cancelled'])->count(), 'stock_value' => (float) WarehouseStock::sum(\DB::raw('quantity * average_cost')), 'due' => (float) Sale::where('status', 'finalized')->sum('due_amount'), 'due_count' => Sale::where('status', 'finalized')->where('due_amount', '>', 0)->count()]]);
    }
}
