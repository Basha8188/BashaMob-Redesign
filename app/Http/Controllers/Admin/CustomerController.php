<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function store(CustomerRequest $r): JsonResponse
    {
        $d = $r->validated();
        $c = Customer::create(['name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'] ?: null, 'notes' => $d['history'] ?: null, 'is_active' => $this->active($d['status']), 'created_by' => $r->user()->id]);
        ActivityLogger::record('customer.created', $c);

        return response()->json(['message' => 'تمت إضافة العميل.', 'row' => $this->row($c)], 201);
    }

    public function update(CustomerRequest $r, Customer $customer): JsonResponse
    {
        $old = $customer->toArray();
        $d = $r->validated();
        $customer->update(['name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'] ?: null, 'notes' => $d['history'] ?: null, 'is_active' => $this->active($d['status']), 'updated_by' => $r->user()->id]);
        ActivityLogger::record('customer.updated', $customer, $old, $customer->fresh()->toArray());

        return response()->json(['message' => 'تم حفظ بيانات العميل.', 'row' => $this->row($customer)]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        abort_unless(request()->user()->can('manage-customers'), 403);
        if ($customer->sales()->exists() || $customer->repairs()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف عميل مرتبط بفواتير أو صيانة. يمكنك إيقافه بدلًا من ذلك.'], 422);
        }$customer->delete();

        return response()->json(['message' => 'تم حذف العميل.']);
    }

    private function active(string $s): bool
    {
        return in_array($s, ['نشط', 'Active'], true);
    }

    private function row(Customer $c): array
    {
        $c->loadCount(['sales', 'repairs']);

        return ['id' => $c->id, 'cells' => [$c->name, $c->phone, $c->email ?? '—', "{$c->sales_count} فواتير / {$c->repairs_count} صيانات", $c->is_active ? 'نشط' : 'موقوف']];
    }
}
