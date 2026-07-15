<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function store(StaffRequest $request): JsonResponse
    {
        $data = $request->validated();
        $password = Str::password(14, true, true, true, false);
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'password' => $password,
            'is_staff' => true, 'is_active' => $this->active($data['status']), 'must_change_password' => true,
        ]);
        $user->assignRole($data['role']);
        ActivityLogger::record('staff.created', $user);

        return response()->json(['message' => "تمت إضافة الموظف. كلمة المرور المؤقتة: {$password}", 'row' => $this->row($user)], 201);
    }

    public function update(StaffRequest $request, User $user): JsonResponse
    {
        abort_unless($user->is_staff, 404);
        $old = $user->toArray();
        $data = $request->validated();
        if ($user->hasRole('Owner') && $data['role'] !== 'Owner' && User::role('Owner')->where('is_active', true)->count() <= 1) {
            return response()->json(['message' => 'لا يمكن تغيير دور آخر Owner نشط.'], 422);
        }
        $user->update(['name' => $data['name'], 'email' => $data['email'], 'is_active' => $this->active($data['status']), 'suspended_at' => $this->active($data['status']) ? null : now()]);
        $user->syncRoles($data['role']);
        ActivityLogger::record('staff.updated', $user, $old, $user->fresh()->toArray());

        return response()->json(['message' => 'تم حفظ اسم الموظف وصلاحياته فعليًا.', 'row' => $this->row($user->fresh())]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_unless(request()->user()->can('manage-staff') && $user->is_staff, 403);
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك أثناء استخدامه.'], 422);
        }
        $user->update(['is_active' => false, 'suspended_at' => now()]);
        ActivityLogger::record('staff.suspended', $user);

        return response()->json(['message' => 'تم إيقاف الموظف بدل حذف سجله.']);
    }

    private function active(string $status): bool
    {
        return in_array($status, ['نشط', 'Active'], true);
    }

    private function row(User $user): array
    {
        $user->load('roles');

        return ['id' => $user->id, 'cells' => [$user->name, $user->email, $user->roles->pluck('name')->join('، '), $user->getAllPermissions()->pluck('name')->join('، '), $user->is_active && ! $user->suspended_at ? 'نشط' : 'موقوف']];
    }
}
