<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\InventoryValuationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $r, InventoryValuationService $inventoryValue): JsonResponse
    {
        abort_unless($r->user()->can('view-reports'), 403);
        $from = $r->date('from') ?? now()->startOfMonth();
        $to = $r->date('to') ?? now()->endOfMonth();

        return response()->json(['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'sales' => Sale::whereBetween('created_at', [$from, $to])->where('status', 'finalized')->sum('total'), 'purchases' => Purchase::whereBetween('created_at', [$from, $to])->sum('total'), 'stock_value' => $inventoryValue->total()]);
    }
}
