<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminModuleDataService;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __invoke(string $module, AdminModuleDataService $data): View
    {
        abort_unless(isset(AdminModuleDataService::META[$module]), 404);

        return view('admin.module', ['module' => $module, 'meta' => AdminModuleDataService::META[$module], 'headers' => AdminModuleDataService::HEADERS, 'addLabels' => AdminModuleDataService::ADD_LABELS, 'rows' => $data->rows($module), 'storeSettings' => $module === 'store' ? Setting::where('group', 'store')->pluck('value', 'key') : collect()]);
    }
}
