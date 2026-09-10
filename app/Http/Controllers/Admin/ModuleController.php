<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Setting;
use App\Services\AdminModuleDataService;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __invoke(string $module, AdminModuleDataService $data): View
    {
        abort_unless(isset(AdminModuleDataService::META[$module]), 404);

        return view('admin.module', [
            'module' => $module,
            'meta' => AdminModuleDataService::META[$module],
            'headers' => AdminModuleDataService::HEADERS,
            'addLabels' => AdminModuleDataService::ADD_LABELS,
            'rows' => $data->rows($module),
            'storeSettings' => $module === 'store' ? Setting::where('group', 'store')->pluck('value', 'key') : collect(),
            'customerRequests' => $module === 'customers' ? collect()
                ->concat(MaintenanceRequest::latest()->limit(20)->get()->map(fn ($item) => ['id' => $item->id, 'type' => 'maintenance', 'number' => $item->request_number, 'customer' => $item->name, 'details' => $item->brand.' '.$item->device.' — '.$item->problem, 'status' => $item->status]))
                ->concat(CustomerOrder::with('customer')->latest()->limit(20)->get()->map(fn ($item) => ['id' => $item->id, 'type' => 'order', 'number' => $item->order_number, 'customer' => $item->customer->name, 'details' => collect($item->items)->pluck('name')->join('، '), 'status' => $item->status]))
                ->sortByDesc('id')->values() : collect(),
            'posProducts' => $module === 'sales' ? Product::withSum('stocks', 'quantity')->orderByDesc('stocks_sum_quantity')->limit(12)->get() : collect(),
        ]);
    }
}
