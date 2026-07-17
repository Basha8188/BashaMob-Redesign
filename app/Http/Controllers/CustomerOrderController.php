<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^01[0-9]{9}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $customer = Customer::withTrashed()->firstOrNew(['phone' => $data['phone']]);
            $customer->fill([
                'name' => $data['name'],
                'email' => $data['email'] ?: $customer->email,
                'is_active' => true,
                'deleted_at' => null,
            ])->save();

            return CustomerOrder::create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $customer->id,
                'items' => $data['items'],
                'total' => collect($data['items'])->sum(fn (array $item) => (float) $item['price']),
                'status' => 'pending_review',
            ]);
        });

        return response()->json([
            'message' => 'تم تسجيل طلب الشراء وسيتم التواصل معك لتأكيده.',
            'order_number' => $order->order_number,
            'pending_count' => CustomerOrder::where('status', 'pending_review')->count(),
        ], 201);
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (CustomerOrder::where('order_number', $number)->exists());

        return $number;
    }
}
