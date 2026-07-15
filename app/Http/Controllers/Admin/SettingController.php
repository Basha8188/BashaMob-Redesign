<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function storeDetails(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-settings'), 403);
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'store_phone' => ['required', 'string', 'max:30'],
            'store_email' => ['required', 'email', 'max:160'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'store_address' => ['required', 'string', 'max:500'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['group' => 'store', 'value' => $value, 'type' => 'string']);
        }
        ActivityLogger::record('store.details.updated', null, [], $data);

        return response()->json(['message' => 'تم حفظ بيانات المحل بنجاح.']);
    }

    public function store(SettingRequest $r): JsonResponse
    {
        $d = $r->validated();
        $s = Setting::create(['group' => $d['section'], 'key' => $d['name'], 'value' => $d['value'], 'type' => 'string']);
        ActivityLogger::record('setting.created', $s);

        return response()->json(['message' => 'تم حفظ الإعداد.', 'row' => $this->row($s)], 201);
    }

    public function update(SettingRequest $r, Setting $setting): JsonResponse
    {
        $old = $setting->toArray();
        $d = $r->validated();
        $setting->update(['group' => $d['section'], 'key' => $d['name'], 'value' => $d['value']]);
        ActivityLogger::record('setting.updated', $setting, $old, $setting->fresh()->toArray());

        return response()->json(['message' => 'تم تحديث الإعداد.', 'row' => $this->row($setting)]);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        abort_unless(request()->user()->can('manage-settings'), 403);
        $setting->delete();

        return response()->json(['message' => 'تم حذف الإعداد.']);
    }

    private function row(Setting $s): array
    {
        return ['id' => $s->id, 'cells' => [$s->group, $s->key, $s->value, 'تعديل', 'فعال']];
    }
}
