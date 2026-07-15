<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    public function store(SupplierRequest $r): JsonResponse
    {
        $d = $r->validated();
        $s = Supplier::create(['name' => $d['name'], 'phone' => $d['phone'] ?: null, 'opening_balance' => $d['balance'], 'is_active' => $d['status'] !== 'معلق', 'created_by' => $r->user()->id]);
        ActivityLogger::record('supplier.created', $s);

        return response()->json(['message' => 'تمت إضافة المورد.', 'row' => $this->row($s)], 201);
    }

    public function update(SupplierRequest $r, Supplier $supplier): JsonResponse
    {
        $old = $supplier->toArray();
        $d = $r->validated();
        $supplier->update(['name' => $d['name'], 'phone' => $d['phone'] ?: null, 'opening_balance' => $d['balance'], 'is_active' => $d['status'] !== 'معلق', 'updated_by' => $r->user()->id]);
        ActivityLogger::record('supplier.updated', $supplier, $old, $supplier->fresh()->toArray());

        return response()->json(['message' => 'تم حفظ بيانات المورد.', 'row' => $this->row($supplier)]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        abort_unless(request()->user()->can('manage-suppliers'), 403);
        if ($supplier->purchases()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف مورد مرتبط بمشتريات.'], 422);
        }$supplier->delete();

        return response()->json(['message' => 'تم حذف المورد.']);
    }

    private function row(Supplier $s): array
    {
        return ['id' => $s->id, 'cells' => [$s->name, $s->phone ?? '—', number_format((float) $s->opening_balance, 2), $s->is_active ? 'مسدد' : 'معلق']];
    }
}
